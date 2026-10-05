<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Actions\Tenant\ChangeTenantStatusAction;
use App\Actions\Tenant\CreateTenantAction;
use App\Actions\Tenant\SoftDeleteTenantAction;
use App\Actions\Tenant\UpdateTenantAction;
use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\DTOs\Tenant\ChangeTenantStatusDTO;
use App\DTOs\Tenant\TenantDomainDTO;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Enums\BillingCycle;
use App\Enums\DomainPanel;
use App\Enums\PaymentGateway;
use App\Enums\ProvisioningStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Enums\WebhookOutcome;
use App\Exceptions\Billing\BillingException;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Filament\Resources\WebhookEvents\Pages\ListWebhookEvents;
use App\Jobs\ProvisionTenantJob;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\Support\TenantData;
use Tests\TestCase;

/**
 * Cobrança de ponta a ponta (RF18, RF27, RF28): do cadastro à suspensão por
 * falta de pagamento, com os gateways simulados.
 */
final class BillingFlowTest extends TestCase
{
    use RefreshDatabase;

    private int $planId;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // O provisionamento criaria um banco de verdade; o resto da fila roda na hora.
        Queue::fake([ProvisionTenantJob::class]);

        $this->planId = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
            new PlanPriceDTO(BillingCycle::Annual, '999.00'),
        ], []))->id;

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_registering_a_tenant_creates_its_subscription_in_the_chosen_gateway(): void
    {
        $tenant = $this->register();
        $subscription = $this->subscription();

        $this->assertSame(PaymentGateway::Asaas, $subscription->gateway);
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertStringStartsWith('cus_test_', (string) $subscription->gateway_customer_id);
        $this->assertStringStartsWith('sub_test_', (string) $subscription->gateway_subscription_id);
        $this->assertSame(TenantStatus::Active, $tenant->status);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v3/subscriptions')
            && $request['value'] === 99.9
            && $request['cycle'] === 'MONTHLY'
            && $request['externalReference'] === 'acme');
    }

    public function test_a_stripe_tenant_gets_a_stripe_subscription_and_the_plan_remembers_its_product(): void
    {
        $this->register(PaymentGateway::Stripe);

        $this->assertSame(PaymentGateway::Stripe, $this->subscription()->gateway);
        $this->assertDatabaseHas('plans', ['id' => $this->planId, 'stripe_product_id' => 'prod_test_1']);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.stripe.com/v1/subscriptions');
    }

    public function test_a_webhook_without_the_right_credentials_is_refused_and_leaves_no_trace(): void
    {
        $this->register();

        $this->postJson('/webhooks/asaas', $this->asaasPayment('PAYMENT_OVERDUE'), ['asaas-access-token' => 'errado'])->assertUnauthorized();
        $this->postJson('/webhooks/asaas', $this->asaasPayment('PAYMENT_OVERDUE'))->assertUnauthorized();
        $this->stripe(['id' => 'evt_1', 'type' => 'invoice.overdue'], secret: 'outro')->assertUnauthorized();
        $this->post('/webhooks/outro-gateway')->assertNotFound();

        $this->assertDatabaseCount('webhook_events', 0);
        $this->assertSame(SubscriptionStatus::Active, $this->subscription()->status);
    }

    public function test_an_overdue_payment_starts_the_grace_period_without_suspending(): void
    {
        $this->register();

        $this->asaas($this->asaasPayment('PAYMENT_OVERDUE'))->assertOk();

        $subscription = $this->subscription();

        $this->assertSame(SubscriptionStatus::Overdue, $subscription->status);
        $this->assertNotNull($subscription->overdue_since);
        $this->assertSame(TenantStatus::Active, $this->tenant()->status, 'Dentro da carência o tenant continua ativo.');
        $this->assertDatabaseHas('webhook_events', ['gateway' => 'asaas', 'type' => 'PAYMENT_OVERDUE', 'outcome' => 'applied', 'tenant_id' => 'acme']);
    }

    public function test_the_same_event_delivered_twice_has_effect_only_once(): void
    {
        $this->register();
        $payload = $this->asaasPayment('PAYMENT_OVERDUE', 'evt_repetido');

        $this->asaas($payload)->assertOk();
        $firstOverdue = $this->subscription()->overdue_since;

        $this->travel(2)->days();
        $this->asaas($payload)->assertOk();

        $this->assertDatabaseCount('webhook_events', 1);
        $this->assertTrue($firstOverdue?->equalTo($this->subscription()->overdue_since));

        // Um segundo aviso de vencimento, com outro id, também não reinicia a carência.
        $this->asaas($this->asaasPayment('PAYMENT_OVERDUE', 'evt_outro'))->assertOk();
        $this->assertTrue($firstOverdue?->equalTo($this->subscription()->overdue_since));
    }

    public function test_the_tenant_is_suspended_only_after_ten_days_overdue_and_then_fully_blocked(): void
    {
        $this->register();
        $this->asaas($this->asaasPayment('PAYMENT_OVERDUE'))->assertOk();

        $this->travel(9)->days();
        $this->artisan('billing:enforce-grace')->assertSuccessful();
        $this->assertSame(TenantStatus::Active, $this->tenant()->status, 'No nono dia ainda está na carência.');
        $this->get('http://painel.acme.test/')->assertRedirect('/admin');
        tenancy()->end();

        $this->travel(1)->days();
        $this->travel(1)->minutes();
        $this->artisan('billing:enforce-grace')->assertSuccessful();

        $this->assertSame(TenantStatus::Suspended, $this->tenant()->status);
        $this->assertDatabaseHas('tenant_status_logs', [
            'tenant_id' => 'acme',
            'to_status' => 'suspended',
            'source' => 'gateway',
            'central_user_id' => null,
            'reason' => 'Pagamento vencido há mais de 10 dias.',
        ]);
        $this->get('http://painel.acme.test/')->assertForbidden()->assertSee('Entre em contato com o administrador');
        tenancy()->end();

        // Rodar de novo não repete a suspensão no histórico.
        $this->artisan('billing:enforce-grace')->assertSuccessful();
        $this->assertDatabaseCount('tenant_status_logs', 1);
    }

    public function test_a_confirmed_payment_clears_the_overdue_and_reactivates_the_tenant(): void
    {
        $this->register();
        $this->asaas($this->asaasPayment('PAYMENT_OVERDUE'));
        $this->travel(11)->days();
        $this->artisan('billing:enforce-grace');
        $this->assertSame(TenantStatus::Suspended, $this->tenant()->status);

        $this->asaas($this->asaasPayment('PAYMENT_RECEIVED', 'evt_pago'))->assertOk();

        $subscription = $this->subscription();

        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertNull($subscription->overdue_since);
        $this->assertNotNull($subscription->last_paid_at);
        $this->assertSame(TenantStatus::Active, $this->tenant()->status);
        $this->assertDatabaseHas('tenant_status_logs', ['tenant_id' => 'acme', 'to_status' => 'active', 'source' => 'gateway', 'reason' => 'Pagamento confirmado.']);
    }

    public function test_a_manual_lock_holds_billing_off_until_it_expires(): void
    {
        $this->register();
        $this->asaas($this->asaasPayment('PAYMENT_OVERDUE'));

        // O central negocia um prazo: trava a situação por 20 dias.
        app(ChangeTenantStatusAction::class)->execute(new ChangeTenantStatusDTO(
            'acme', TenantStatus::Active, 'Acordo: paga até o fim do mês.', CarbonImmutable::now()->addDays(20), $this->admin->id,
        ));

        $this->travel(15)->days();
        $this->artisan('billing:enforce-grace');
        $this->asaas($this->asaasSubscription('SUBSCRIPTION_DELETED'));

        $this->assertSame(TenantStatus::Active, $this->tenant()->status, 'Durante a trava a cobrança não altera a situação.');
        $this->assertSame(SubscriptionStatus::Canceled, $this->subscription()->status, 'Mas o que o gateway informou fica registrado.');

        // Vencida a trava, a cobrança volta a valer.
        $this->asaas($this->asaasPayment('PAYMENT_OVERDUE', 'evt_depois'));
        $this->travel(6)->days();
        $this->artisan('billing:enforce-grace');

        $this->assertSame(TenantStatus::Suspended, $this->tenant()->status);
    }

    public function test_a_canceled_subscription_suspends_the_tenant_at_once(): void
    {
        $this->register();

        $this->asaas($this->asaasSubscription('SUBSCRIPTION_DELETED'))->assertOk();

        $this->assertSame(SubscriptionStatus::Canceled, $this->subscription()->status);
        $this->assertSame(TenantStatus::Suspended, $this->tenant()->status);
        $this->assertDatabaseHas('tenant_status_logs', ['tenant_id' => 'acme', 'source' => 'gateway', 'reason' => 'Assinatura cancelada no gateway.']);
    }

    public function test_events_without_effect_or_without_a_known_subscription_are_kept_and_change_nothing(): void
    {
        $this->register();

        $this->asaas($this->asaasPayment('PAYMENT_CREATED', 'evt_sem_efeito'))->assertOk();
        $this->asaas(['id' => 'evt_desconhecido', 'event' => 'PAYMENT_OVERDUE', 'payment' => ['customer' => 'cus_de_outro_sistema', 'subscription' => 'sub_de_outro_sistema']])->assertOk();
        $this->asaas(['evento' => 'sem identificador'])->assertOk();

        $this->assertSame(WebhookOutcome::Ignored, WebhookEvent::query()->where('gateway_event_id', 'evt_sem_efeito')->firstOrFail()->outcome);
        $this->assertSame(WebhookOutcome::Unmatched, WebhookEvent::query()->where('gateway_event_id', 'evt_desconhecido')->firstOrFail()->outcome);
        $this->assertDatabaseCount('webhook_events', 2);
        $this->assertSame(SubscriptionStatus::Active, $this->subscription()->status);
    }

    public function test_stripe_events_follow_the_same_rules(): void
    {
        $this->register(PaymentGateway::Stripe);
        $customer = $this->subscription()->gateway_customer_id;

        $this->stripe(['id' => 'evt_s1', 'type' => 'invoice.overdue', 'data' => ['object' => ['customer' => $customer]]])->assertOk();
        $this->assertSame(SubscriptionStatus::Overdue, $this->subscription()->status);

        $this->travel(11)->days();
        $this->artisan('billing:enforce-grace');
        $this->assertSame(TenantStatus::Suspended, $this->tenant()->status);

        $this->stripe(['id' => 'evt_s2', 'type' => 'invoice.paid', 'data' => ['object' => ['customer' => $customer]]])->assertOk();
        $this->assertSame(TenantStatus::Active, $this->tenant()->status);

        $this->stripe(['id' => 'evt_s3', 'type' => 'customer.subscription.deleted', 'data' => ['object' => ['id' => $this->subscription()->gateway_subscription_id, 'customer' => $customer]]])->assertOk();
        $this->assertSame(TenantStatus::Suspended, $this->tenant()->status);
    }

    public function test_changing_plan_or_cycle_updates_the_next_charges_in_the_gateway(): void
    {
        $this->register();
        $subscriptionId = $this->subscription()->gateway_subscription_id;

        app(UpdateTenantAction::class)->execute(new UpdateTenantDTO(
            tenantId: 'acme',
            company: TenantData::company(),
            planId: $this->planId,
            billingCycle: BillingCycle::Annual,
            domains: [new TenantDomainDTO('painel.acme.test', DomainPanel::Admin)],
        ));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), "/v3/subscriptions/{$subscriptionId}")
            && $request['value'] === 999.0
            && $request['cycle'] === 'YEARLY');
    }

    public function test_without_credentials_the_subscription_fails_visibly_and_can_be_retried(): void
    {
        config(['billing.asaas.api_key' => null]);

        $tenant = $this->register();
        $subscription = $this->subscription();

        $this->assertSame(SubscriptionStatus::Failed, $subscription->status);
        $this->assertStringContainsString('não está configurado', (string) $subscription->last_error);
        $this->assertNull($subscription->gateway_subscription_id);

        // Corrigida a configuração, o central manda tentar de novo.
        config(['billing.asaas.api_key' => 'asaas-test-key']);
        $this->actingAs($this->admin);

        Livewire::test(ListTenants::class)
            ->assertTableActionVisible('retrySubscription', $tenant)
            ->callTableAction('retrySubscription', $tenant)
            ->assertNotified();

        $this->assertSame(SubscriptionStatus::Active, $this->subscription()->status);
        $this->assertNull($this->subscription()->last_error);

        // Criada, a nova tentativa deixa de ser oferecida: nada é duplicado.
        Livewire::test(ListTenants::class)->assertTableActionHidden('retrySubscription', $tenant);
    }

    public function test_a_refusal_from_the_gateway_is_recorded_on_the_subscription(): void
    {
        $this->gatewayResponse = fn (Request $request) => str_ends_with($request->url(), '/customers')
            ? Http::response(['errors' => [['description' => 'O CPF/CNPJ informado é inválido.']]], 400)
            : null;

        try {
            $this->register();
        } catch (BillingException) {
            // Com a fila síncrona dos testes a recusa sobe; em produção, a fila tenta de novo.
        }

        $this->assertSame(SubscriptionStatus::Failed, $this->subscription()->status);
        $this->assertStringContainsString('O CPF/CNPJ informado é inválido.', (string) $this->subscription()->last_error);
        $this->assertSame(TenantStatus::Active, $this->tenant()->status, 'Falha ao criar a cobrança não suspende ninguém.');
    }

    public function test_billing_events_of_a_deleted_tenant_are_recorded_without_failing(): void
    {
        $this->register();
        app(SoftDeleteTenantAction::class)->execute('acme');

        $this->asaas($this->asaasSubscription('SUBSCRIPTION_DELETED'))->assertOk();

        $this->assertSame(SubscriptionStatus::Canceled, $this->subscription()->status);
        $this->assertDatabaseHas('webhook_events', ['type' => 'SUBSCRIPTION_DELETED', 'outcome' => 'applied']);
        $this->assertDatabaseCount('tenant_status_logs', 0);
    }

    public function test_the_central_sees_billing_status_and_the_received_events(): void
    {
        $this->register();
        $this->asaas($this->asaasPayment('PAYMENT_OVERDUE'));
        $this->actingAs(User::factory()->create(['role' => UserRole::Operator]));

        Livewire::test(ListTenants::class)->assertSuccessful()->assertSee('Vencida');

        Livewire::test(ListWebhookEvents::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(WebhookEvent::query()->get())
            ->assertSee('PAYMENT_OVERDUE');
    }

    private function register(PaymentGateway $gateway = PaymentGateway::Asaas): Tenant
    {
        $tenant = app(CreateTenantAction::class)->execute(TenantData::create(planId: $this->planId, gateway: $gateway));

        // Ambiente pronto e domínio verificado, para as requisições ao domínio do tenant.
        app(TenantRepositoryInterface::class)->updateProvisioning($tenant, ProvisioningStatus::Ready);
        $tenant->domains()->update(['status' => 'active']);

        return $tenant->refresh();
    }

    private function tenant(): Tenant
    {
        return Tenant::withTrashed()->findOrFail('acme');
    }

    private function subscription(): Subscription
    {
        return Subscription::query()->where('tenant_id', 'acme')->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function asaasPayment(string $event, string $id = 'evt_1'): array
    {
        $subscription = $this->subscription();

        return ['id' => $id, 'event' => $event, 'payment' => [
            'id' => 'pay_1',
            'customer' => $subscription->gateway_customer_id,
            'subscription' => $subscription->gateway_subscription_id,
        ]];
    }

    /** @return array<string, mixed> */
    private function asaasSubscription(string $event, string $id = 'evt_sub_1'): array
    {
        $subscription = $this->subscription();

        return ['id' => $id, 'event' => $event, 'subscription' => [
            'id' => $subscription->gateway_subscription_id,
            'customer' => $subscription->gateway_customer_id,
        ]];
    }

    /** @param array<string, mixed> $payload */
    private function asaas(array $payload): TestResponse
    {
        return $this->postJson('/webhooks/asaas', $payload, ['asaas-access-token' => 'asaas-webhook-token']);
    }

    /** @param array<string, mixed> $payload */
    private function stripe(array $payload, string $secret = 'whsec_test_fake'): TestResponse
    {
        $body = (string) json_encode($payload);
        $timestamp = now()->getTimestamp();
        $signature = "t={$timestamp},v1=".hash_hmac('sha256', "{$timestamp}.{$body}", $secret);

        return $this->call('POST', '/webhooks/stripe', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], content: $body);
    }
}
