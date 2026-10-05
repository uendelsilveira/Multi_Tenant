<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Actions\Customer\SyncCustomerResponsiblesAction;
use App\Actions\Tenant\CreateTenantAction;
use App\Actions\TenantUser\DeactivateTenantUserAction;
use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\DTOs\Tenant\TenantDomainDTO;
use App\Enums\BillingCycle;
use App\Enums\DomainPanel;
use App\Enums\TenantUserType;
use App\Exceptions\Customer\CustomerRuleException;
use App\Exceptions\TenantUser\TenantUserNotFoundException;
use App\Filament\Tenant\Admin\Resources\Customers\Pages\ListCustomers as AdminListCustomers;
use App\Filament\Tenant\Admin\Resources\People\Pages\ListPeople;
use App\Filament\Tenant\User\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Tenant\User\Resources\Customers\Pages\EditCustomer;
use App\Filament\Tenant\User\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Notifications\ProvisionalPasswordNotification;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Services\CustomerService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TenantData;
use Tests\TestCase;

/**
 * Clientes de um tenant (RF16, RF17), com o MySQL de verdade: o usuário
 * cadastra, cada um vê os seus e o admin cuida dos vínculos.
 */
#[Group('real-database')]
final class TenantCustomersTest extends TestCase
{
    private string $tenantId;

    private string $databaseName;

    private string $adminHost;

    private string $userHost;

    private string $customerHost;

    private ?int $planId = null;

    private Tenant $tenant;

    private TenantUser $admin;

    private TenantUser $bruno;

    private TenantUser $dora;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate')->assertSuccessful();

        Notification::fake();

        $this->tenantId = 'cust-'.Str::lower(Str::random(8));
        $this->databaseName = config('tenancy.database.prefix').$this->tenantId.config('tenancy.database.suffix');
        $this->adminHost = "painel.{$this->tenantId}.test";
        $this->userHost = "app.{$this->tenantId}.test";
        $this->customerHost = "portal.{$this->tenantId}.test";

        $this->planId = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO("Plano {$this->tenantId}", null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []))->id;

        // A fila é síncrona nos testes: o provisionamento roda dentro do cadastro.
        $this->tenant = app(CreateTenantAction::class)->execute(TenantData::create(
            slug: $this->tenantId,
            planId: (int) $this->planId,
            domains: [
                new TenantDomainDTO($this->adminHost, DomainPanel::Admin),
                new TenantDomainDTO($this->userHost, DomainPanel::User),
                new TenantDomainDTO($this->customerHost, DomainPanel::Customer),
            ],
            company: TenantData::company(TenantData::VALID_ALPHANUMERIC_CNPJ),
        ))->refresh();

        DB::table('domains')->where('tenant_id', $this->tenantId)->update(['status' => 'active', 'verified_at' => now()]);

        tenancy()->initialize($this->tenant);
        TenantUser::query()->update(['must_change_password' => false, 'password_expires_at' => null]);

        $userRoleId = (int) Role::query()->where('is_system', true)->where('base_type', 'user')->valueOrFail('id');

        $this->admin = TenantUser::query()->with('role')->firstOrFail();
        $this->bruno = $this->staff('Bruno Lima', 'bruno@acme.test', $userRoleId);
        $this->dora = $this->staff('Dora Reis', 'dora@acme.test', $userRoleId);
    }

    protected function tearDown(): void
    {
        $this->leaveTenant();

        DB::statement("DROP DATABASE IF EXISTS `{$this->databaseName}`");
        DB::table('domains')->where('tenant_id', $this->tenantId)->delete();
        DB::table('tenants')->where('id', $this->tenantId)->delete();

        if ($this->planId !== null) {
            DB::table('plans')->where('id', $this->planId)->delete();
        }

        parent::tearDown();
    }

    public function test_a_user_registers_a_customer_who_is_linked_to_them_and_gets_portal_access(): void
    {
        $customer = $this->registerCustomer($this->bruno, 'Cliente Um', 'um@cliente.test');

        $this->assertSame(TenantUserType::Customer, $customer->role?->base_type);
        $this->assertSame('51999990000', $customer->profile?->phone);
        $this->assertSame('52998224725', $customer->profile?->document);
        $this->assertSame([$this->bruno->id], $customer->responsibles->modelKeys());
        $this->assertTrue($customer->must_change_password);

        // Todo cliente recebe acesso: senha provisória por e-mail, com o endereço do portal.
        $person = TenantUser::query()->findOrFail($customer->id);

        Notification::assertSentTo($person, ProvisionalPasswordNotification::class, function (ProvisionalPasswordNotification $notification) use ($person): bool {
            return Hash::check($notification->plainPassword, (string) $person->getAuthPassword())
                && $notification->loginUrl === "http://{$this->customerHost}/portal/login";
        });
    }

    public function test_each_user_sees_only_their_own_customers(): void
    {
        $ofBruno = $this->registerCustomer($this->bruno, 'Cliente do Bruno', 'bruno.cliente@cliente.test');
        $ofDora = $this->registerCustomer($this->dora, 'Cliente da Dora', 'dora.cliente@cliente.test', document: null);

        $this->actAs($this->bruno, DomainPanel::User);

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords([$ofBruno])
            ->assertCanNotSeeTableRecords([$ofDora]);

        // Nem pelo endereço direto o cliente de outro usuário abre.
        $this->visit($this->bruno, "http://{$this->userHost}/app/clientes/{$ofDora->id}/edit")->assertNotFound();
        $this->visit($this->bruno, "http://{$this->userHost}/app/clientes/{$ofBruno->id}/edit")->assertOk();
    }

    public function test_a_user_edits_the_data_of_their_customer(): void
    {
        $customer = $this->registerCustomer($this->bruno, 'Cliente Um', 'um@cliente.test');

        $this->actAs($this->bruno, DomainPanel::User);

        Livewire::test(EditCustomer::class, ['record' => $customer->id])
            ->assertFormSet(['name' => 'Cliente Um', 'phone' => '51999990000', 'document' => '52998224725'])
            ->fillForm(['name' => 'Cliente Um Ltda', 'phone' => '(51) 3333-0000', 'document' => '11.222.333/0001-81', 'notes' => 'Prefere contato à tarde.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $customer = Customer::query()->with('profile')->findOrFail($customer->id);

        $this->assertSame('Cliente Um Ltda', $customer->name);
        $this->assertSame('5133330000', $customer->profile?->phone);
        $this->assertSame('11222333000181', $customer->profile?->document);
        $this->assertSame('Prefere contato à tarde.', $customer->profile?->notes);
    }

    public function test_an_email_already_in_the_tenant_is_refused_with_guidance(): void
    {
        $this->registerCustomer($this->bruno, 'Cliente Um', 'um@cliente.test');

        $this->actAs($this->dora, DomainPanel::User);

        Livewire::test(CreateCustomer::class)
            ->fillForm(['name' => 'Mesmo cliente', 'email' => 'um@cliente.test'])
            ->call('create')
            ->assertNotified();

        $this->assertSame(1, Customer::query()->count());

        // Vale para qualquer pessoa do tenant, não só clientes.
        Livewire::test(CreateCustomer::class)
            ->fillForm(['name' => 'E-mail do Bruno', 'email' => 'bruno@acme.test'])
            ->call('create')
            ->assertNotified();

        $this->assertSame(1, Customer::query()->count());
    }

    public function test_the_admin_sees_every_customer_and_manages_who_attends_each_one(): void
    {
        $customer = $this->registerCustomer($this->bruno, 'Cliente Um', 'um@cliente.test');
        $other = $this->registerCustomer($this->dora, 'Cliente Dois', 'dois@cliente.test', document: null);

        $this->actAs($this->admin, DomainPanel::Admin);

        Livewire::test(AdminListCustomers::class)
            ->assertCanSeeTableRecords([$customer, $other])
            // A Dora passa a atender também o primeiro cliente: o vínculo é N:N.
            ->callTableAction('manageResponsibles', $customer, data: ['user_ids' => [$this->bruno->id, $this->dora->id]])
            ->assertNotified();

        $this->assertEqualsCanonicalizing([$this->bruno->id, $this->dora->id], $customer->responsibles()->pluck('users.id')->all());

        $this->actAs($this->dora, DomainPanel::User);
        Livewire::test(ListCustomers::class)->assertCanSeeTableRecords([$customer, $other]);

        // O Bruno deixa de atender: perde o acesso ao cliente.
        $this->actAs($this->admin, DomainPanel::Admin);
        Livewire::test(AdminListCustomers::class)
            ->callTableAction('manageResponsibles', $customer, data: ['user_ids' => [$this->dora->id]]);

        $this->actAs($this->bruno, DomainPanel::User);
        Livewire::test(ListCustomers::class)->assertCanNotSeeTableRecords([$customer]);
        $this->visit($this->bruno, "http://{$this->userHost}/app/clientes/{$customer->id}/edit")->assertNotFound();
    }

    public function test_only_active_users_can_be_responsible_and_one_always_remains(): void
    {
        $customer = $this->registerCustomer($this->bruno, 'Cliente Um', 'um@cliente.test');
        $sync = app(SyncCustomerResponsiblesAction::class);

        app(DeactivateTenantUserAction::class)->execute($this->dora->id, $this->admin->id);

        $refused = [
            'um admin não é responsável por cliente' => [[$this->admin->id], CustomerRuleException::invalidResponsible()],
            'nem um usuário desativado' => [[$this->dora->id], CustomerRuleException::invalidResponsible()],
            'nem alguém que não existe' => [[999999], CustomerRuleException::invalidResponsible()],
            'e a lista não pode ficar vazia' => [[], CustomerRuleException::needsResponsible()],
        ];

        foreach ($refused as $case => [$userIds, $expected]) {
            try {
                $sync->execute($customer->id, $userIds);
                $this->fail("Deveria ter sido recusado: {$case}.");
            } catch (CustomerRuleException $e) {
                $this->assertEquals($expected, $e, $case);
            }
        }

        $this->assertSame([$this->bruno->id], $customer->responsibles()->pluck('users.id')->all());

        // Na tela, a seleção só oferece usuários ativos.
        $this->assertSame([$this->bruno->id => 'Bruno Lima'], app(CustomerService::class)->responsibleOptions());
    }

    public function test_the_admin_does_not_register_customers_but_can_deactivate_them(): void
    {
        $customer = $this->registerCustomer($this->bruno, 'Cliente Um', 'um@cliente.test');
        TenantUser::query()->whereKey($customer->id)->update(['must_change_password' => false, 'password_expires_at' => null]);
        $person = TenantUser::query()->findOrFail($customer->id);

        $this->visit($person, "http://{$this->customerHost}/portal")->assertOk();

        $this->actAs($this->admin, DomainPanel::Admin);

        $this->assertFalse(Filament::auth()->user()?->can('create', Customer::class));

        Livewire::test(AdminListCustomers::class)->callTableAction('deactivate', $customer);

        $this->visit($person->refresh(), "http://{$this->customerHost}/portal")->assertForbidden();

        $this->actAs($this->admin, DomainPanel::Admin);
        Livewire::test(AdminListCustomers::class)->callTableAction('activate', $customer);

        $this->visit($person->refresh(), "http://{$this->customerHost}/portal")->assertOk();
    }

    public function test_a_customer_only_enters_the_portal_and_is_out_of_people_management(): void
    {
        $customer = $this->registerCustomer($this->bruno, 'Cliente Um', 'um@cliente.test');
        TenantUser::query()->whereKey($customer->id)->update(['must_change_password' => false, 'password_expires_at' => null]);
        $person = TenantUser::query()->findOrFail($customer->id);

        $this->visit($person, "http://{$this->customerHost}/portal")->assertOk();
        $this->visit($person, "http://{$this->userHost}/app")->assertForbidden();
        $this->visit($person, "http://{$this->adminHost}/admin")->assertForbidden();

        // A tela de pessoas do admin não lista clientes, e as ações de pessoas não os alcançam.
        $this->actAs($this->admin, DomainPanel::Admin);

        Livewire::test(ListPeople::class)
            ->assertCanSeeTableRecords([$this->bruno, $this->dora])
            ->assertCanNotSeeTableRecords([$person]);

        $this->expectException(TenantUserNotFoundException::class);

        app(DeactivateTenantUserAction::class)->execute($customer->id, $this->admin->id);
    }

    /** Cadastro feito pelo usuário, pela tela dele. */
    private function registerCustomer(TenantUser $user, string $name, string $email, ?string $document = '529.982.247-25'): Customer
    {
        $this->actAs($user, DomainPanel::User);

        Livewire::test(CreateCustomer::class)
            ->fillForm(['name' => $name, 'email' => $email, 'phone' => '(51) 99999-0000', 'document' => $document])
            ->call('create')
            ->assertHasNoFormErrors();

        return Customer::query()->with(['role', 'profile', 'responsibles'])->where('email', $email)->firstOrFail();
    }

    /** Pessoa da equipe já com o primeiro acesso feito, criada direto no banco do tenant. */
    private function staff(string $name, string $email, int $roleId): TenantUser
    {
        return TenantUser::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'SenhaDeTeste123',
            'role_id' => $roleId,
        ])->load('role');
    }

    /** Coloca o teste dentro de um painel do tenant, como a pessoa indicada. */
    private function actAs(TenantUser $person, DomainPanel $panel): void
    {
        if (! tenancy()->initialized) {
            tenancy()->initialize($this->tenant);
        }

        Filament::setCurrentPanel(Filament::getPanel($panel->panelId()));

        $this->flushSession();
        $this->actingAs($person, 'tenant');
    }

    /** Requisição de verdade, resolvida pelo domínio, como a pessoa indicada. */
    private function visit(TenantUser $person, string $url): TestResponse
    {
        $this->leaveTenant();
        $this->flushSession();

        $response = $this->actingAs($person, 'tenant')->get($url);

        $this->leaveTenant();
        tenancy()->initialize($this->tenant);

        return $response;
    }

    private function leaveTenant(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
    }
}
