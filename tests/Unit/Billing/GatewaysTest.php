<?php

declare(strict_types=1);

namespace Tests\Unit\Billing;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\BillingCycle;
use App\Enums\BillingEventType;
use App\Enums\PaymentGateway;
use App\Exceptions\Billing\BillingException;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Billing\PaymentGatewayRegistry;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Os dois gateways, contra respostas simuladas. Nenhuma chamada sai para a API real.
 */
final class GatewaysTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-05 12:00:00');
    }

    public function test_asaas_creates_the_payer_and_the_recurring_charge(): void
    {
        $created = $this->gateway(PaymentGateway::Asaas)->createSubscription($this->tenant(), $this->plan(), $this->price(BillingCycle::Semiannual, '599.40'));

        $this->assertStringStartsWith('cus_test_', $created->customerId);
        $this->assertStringStartsWith('sub_test_', $created->subscriptionId);
        $this->assertNull($created->productId);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/customers'
            && $request->method() === 'POST'
            && $request->hasHeader('access_token', 'asaas-test-key')
            && $request['name'] === 'Acme Ltda'
            && $request['cpfCnpj'] === '11222333000181'
            && $request['email'] === 'ana@acme.test'
            && $request['externalReference'] === 'acme');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/subscriptions'
            && $request['customer'] === $created->customerId
            && $request['billingType'] === 'UNDEFINED'
            && $request['value'] === 599.4
            && $request['cycle'] === 'SEMIANNUALLY'
            && $request['nextDueDate'] === '2026-10-12'
            && $request['externalReference'] === 'acme');
    }

    public function test_asaas_updates_value_and_cycle_of_future_charges(): void
    {
        $subscription = new Subscription(['gateway_subscription_id' => 'sub_abc']);

        $this->gateway(PaymentGateway::Asaas)->updateSubscription($subscription, $this->plan(), $this->price(BillingCycle::Annual, '999.00'));

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api-sandbox.asaas.com/v3/subscriptions/sub_abc'
            && $request->method() === 'PUT'
            && $request['value'] === 999.0
            && $request['cycle'] === 'YEARLY');
    }

    public function test_asaas_accepts_only_webhooks_carrying_the_configured_token(): void
    {
        $gateway = $this->gateway(PaymentGateway::Asaas);

        $this->assertTrue($gateway->verifyWebhook('{}', ['asaas-access-token' => 'asaas-webhook-token']));
        $this->assertFalse($gateway->verifyWebhook('{}', ['asaas-access-token' => 'outro']));
        $this->assertFalse($gateway->verifyWebhook('{}', []));

        // Sem token configurado, nada é aceito: token vazio não pode casar com cabeçalho vazio.
        config(['billing.asaas.webhook_token' => null]);
        $this->assertFalse($this->gateway(PaymentGateway::Asaas)->verifyWebhook('{}', ['asaas-access-token' => '']));
    }

    public function test_asaas_events_are_translated_to_platform_events(): void
    {
        $gateway = $this->gateway(PaymentGateway::Asaas);

        $cases = [
            'PAYMENT_CONFIRMED' => BillingEventType::PaymentConfirmed,
            'PAYMENT_RECEIVED' => BillingEventType::PaymentConfirmed,
            'PAYMENT_OVERDUE' => BillingEventType::PaymentOverdue,
            'PAYMENT_CREATED' => null,
        ];

        foreach ($cases as $raw => $expected) {
            $event = $gateway->parseWebhook(['id' => 'evt_1', 'event' => $raw, 'payment' => ['customer' => 'cus_1', 'subscription' => 'sub_1']]);

            $this->assertNotNull($event);
            $this->assertSame($expected, $event->type, $raw);
            $this->assertSame('cus_1', $event->customerId);
            $this->assertSame('sub_1', $event->subscriptionId);
        }

        $deleted = $gateway->parseWebhook(['id' => 'evt_2', 'event' => 'SUBSCRIPTION_DELETED', 'subscription' => ['id' => 'sub_1', 'customer' => 'cus_1']]);

        $this->assertSame(BillingEventType::SubscriptionCanceled, $deleted?->type);
        $this->assertSame('sub_1', $deleted?->subscriptionId);
        $this->assertNull($gateway->parseWebhook(['event' => 'PAYMENT_OVERDUE']), 'Sem id não há como garantir que o evento entra uma vez só.');
    }

    public function test_stripe_creates_product_payer_and_an_invoiced_subscription(): void
    {
        $created = $this->gateway(PaymentGateway::Stripe)->createSubscription($this->tenant(), $this->plan(), $this->price(BillingCycle::Semiannual, '599.40'));

        $this->assertSame('prod_test_1', $created->productId);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.stripe.com/v1/products'
            && $request->hasHeader('Authorization', 'Bearer sk_test_fake'));

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.stripe.com/v1/customers'
            && $request['email'] === 'ana@acme.test'
            && $request['metadata']['tenant_id'] === 'acme');

        Http::assertSent(function (Request $request) use ($created): bool {
            if ($request->url() !== 'https://api.stripe.com/v1/subscriptions') {
                return false;
            }

            $price = $request['items'][0]['price_data'];

            return $request['customer'] === $created->customerId
                && $request['collection_method'] === 'send_invoice'
                && (int) $request['days_until_due'] === 7
                && $price['product'] === 'prod_test_1'
                && $price['currency'] === 'brl'
                && (int) $price['unit_amount'] === 59940
                && $price['recurring']['interval'] === 'month'
                && (int) $price['recurring']['interval_count'] === 6;
        });
    }

    public function test_stripe_reuses_the_product_of_a_plan_already_sent(): void
    {
        $plan = $this->plan();
        $plan->stripe_product_id = 'prod_existente';

        $created = $this->gateway(PaymentGateway::Stripe)->createSubscription($this->tenant(), $plan, $this->price(BillingCycle::Monthly, '99.90'));

        $this->assertSame('prod_existente', $created->productId);
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/products'));
    }

    public function test_stripe_changes_the_price_without_prorating(): void
    {
        $subscription = new Subscription(['gateway_subscription_id' => 'sub_abc']);
        $plan = $this->plan();
        $plan->stripe_product_id = 'prod_existente';

        $this->gateway(PaymentGateway::Stripe)->updateSubscription($subscription, $plan, $this->price(BillingCycle::Annual, '999.00'));

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.stripe.com/v1/subscriptions/sub_abc'
            && $request->method() === 'POST'
            && $request['items'][0]['id'] === 'si_test_1'
            && (int) $request['items'][0]['price_data']['unit_amount'] === 99900
            && $request['items'][0]['price_data']['recurring']['interval'] === 'year'
            && $request['proration_behavior'] === 'none');
    }

    public function test_stripe_accepts_only_webhooks_with_a_valid_and_recent_signature(): void
    {
        $gateway = $this->gateway(PaymentGateway::Stripe);
        $payload = '{"id":"evt_1","type":"invoice.paid"}';
        $now = Carbon::now()->getTimestamp();

        $sign = fn (int $timestamp, string $body, string $secret = 'whsec_test_fake'): string => "t={$timestamp},v1=".hash_hmac('sha256', "{$timestamp}.{$body}", $secret);

        $this->assertTrue($gateway->verifyWebhook($payload, ['stripe-signature' => $sign($now, $payload)]));
        $this->assertTrue($gateway->verifyWebhook($payload, ['stripe-signature' => "t={$now},v1=errada,v1=".hash_hmac('sha256', "{$now}.{$payload}", 'whsec_test_fake')]));

        $this->assertFalse($gateway->verifyWebhook($payload.' ', ['stripe-signature' => $sign($now, $payload)]), 'Corpo alterado.');
        $this->assertFalse($gateway->verifyWebhook($payload, ['stripe-signature' => $sign($now, $payload, 'outro-segredo')]), 'Segredo errado.');
        $this->assertFalse($gateway->verifyWebhook($payload, ['stripe-signature' => $sign($now - 301, $payload)]), 'Assinatura antiga demais.');
        $this->assertFalse($gateway->verifyWebhook($payload, ['stripe-signature' => 'v1=abc']), 'Sem timestamp.');
        $this->assertFalse($gateway->verifyWebhook($payload, []), 'Sem cabeçalho.');
    }

    public function test_stripe_events_are_translated_to_platform_events(): void
    {
        $gateway = $this->gateway(PaymentGateway::Stripe);

        $cases = [
            'invoice.paid' => BillingEventType::PaymentConfirmed,
            'invoice.overdue' => BillingEventType::PaymentOverdue,
            'invoice.payment_failed' => BillingEventType::PaymentOverdue,
            'invoice.created' => null,
        ];

        foreach ($cases as $raw => $expected) {
            $event = $gateway->parseWebhook(['id' => 'evt_1', 'type' => $raw, 'data' => ['object' => ['customer' => 'cus_1']]]);

            $this->assertSame($expected, $event?->type, $raw);
            $this->assertSame('cus_1', $event?->customerId);
        }

        $deleted = $gateway->parseWebhook(['id' => 'evt_2', 'type' => 'customer.subscription.deleted', 'data' => ['object' => ['id' => 'sub_1', 'customer' => 'cus_1']]]);

        $this->assertSame(BillingEventType::SubscriptionCanceled, $deleted?->type);
        $this->assertSame('sub_1', $deleted?->subscriptionId);
    }

    public function test_a_gateway_without_credentials_or_that_refuses_the_request_fails_clearly(): void
    {
        $this->gatewayResponse = fn (Request $request) => Http::response(['errors' => [['description' => 'CPF/CNPJ inválido']]], 400);

        try {
            $this->gateway(PaymentGateway::Asaas)->createSubscription($this->tenant(), $this->plan(), $this->price(BillingCycle::Monthly, '99.90'));
            $this->fail('A recusa do gateway deveria virar exceção.');
        } catch (BillingException $e) {
            $this->assertStringContainsString('HTTP 400', $e->getMessage());
            $this->assertStringContainsString('CPF/CNPJ inválido', $e->getMessage());
        }

        config(['billing.stripe.secret' => null]);

        $this->assertFalse($this->gateway(PaymentGateway::Stripe)->isConfigured());
        $this->expectExceptionObject(BillingException::gatewayNotConfigured(PaymentGateway::Stripe));

        $this->gateway(PaymentGateway::Stripe)->createSubscription($this->tenant(), $this->plan(), $this->price(BillingCycle::Monthly, '99.90'));
    }

    private function gateway(PaymentGateway $gateway): PaymentGatewayInterface
    {
        return app(PaymentGatewayRegistry::class)->for($gateway);
    }

    private function tenant(): Tenant
    {
        $tenant = new Tenant;
        $tenant->id = 'acme';
        $tenant->legal_name = 'Acme Ltda';
        $tenant->document = '11222333000181';
        $tenant->contact_email = 'ana@acme.test';
        $tenant->contact_phone = '51999990000';
        $tenant->zip_code = '90000000';
        $tenant->street = 'Rua A';
        $tenant->number = '10';
        $tenant->district = 'Centro';

        return $tenant;
    }

    private function plan(): Plan
    {
        $plan = new Plan(['name' => 'Profissional', 'is_active' => true]);
        $plan->id = 1;

        return $plan;
    }

    private function price(BillingCycle $cycle, string $value): PlanPrice
    {
        return new PlanPrice(['billing_cycle' => $cycle->value, 'price' => $value]);
    }
}
