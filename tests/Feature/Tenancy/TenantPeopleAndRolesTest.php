<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Actions\Tenant\CreateTenantAction;
use App\Actions\TenantUser\CreateTenantUserAction;
use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\DTOs\Tenant\TenantDomainDTO;
use App\DTOs\TenantUser\CreateTenantUserDTO;
use App\Enums\BillingCycle;
use App\Enums\DomainPanel;
use App\Enums\TenantUserType;
use App\Exceptions\TenantUser\TenantUserRuleException;
use App\Filament\Tenant\Admin\Resources\People\Pages\CreatePerson;
use App\Filament\Tenant\Admin\Resources\People\Pages\EditPerson;
use App\Filament\Tenant\Admin\Resources\People\Pages\ListPeople;
use App\Filament\Tenant\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Tenant\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Tenant\Admin\Resources\Roles\Pages\ListRoles;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Notifications\ProvisionalPasswordNotification;
use App\Repositories\Contracts\PlanRepositoryInterface;
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
 * Pessoas e perfis dentro de um tenant (RF12, RF13), com o MySQL de verdade:
 * as telas do painel admin rodam contra o banco do próprio tenant.
 */
#[Group('real-database')]
final class TenantPeopleAndRolesTest extends TestCase
{
    private string $tenantId;

    private string $databaseName;

    private string $adminHost;

    private string $userHost;

    private ?int $planId = null;

    private Tenant $tenant;

    private TenantUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate')->assertSuccessful();

        Notification::fake();

        $this->tenantId = 'ppl-'.Str::lower(Str::random(8));
        $this->databaseName = config('tenancy.database.prefix').$this->tenantId.config('tenancy.database.suffix');
        $this->adminHost = "painel.{$this->tenantId}.test";
        $this->userHost = "app.{$this->tenantId}.test";

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
            ],
            company: TenantData::company(TenantData::VALID_ALPHANUMERIC_CNPJ),
        ))->refresh();

        DB::table('domains')->where('tenant_id', $this->tenantId)->update(['status' => 'active', 'verified_at' => now()]);

        // O admin inicial já passou pelo primeiro acesso.
        $this->enterTenant();
        TenantUser::query()->update(['must_change_password' => false, 'password_expires_at' => null]);
        $this->admin = TenantUser::query()->with('role')->firstOrFail();
        $this->actAs($this->admin);
    }

    protected function tearDown(): void
    {
        $this->leaveTenant();

        DB::statement("DROP DATABASE IF EXISTS `{$this->databaseName}`");
        DB::table('domains')->where('tenant_id', $this->tenantId)->delete();
        DB::table('subscriptions')->where('tenant_id', $this->tenantId)->delete();
        DB::table('tenants')->where('id', $this->tenantId)->delete();

        if ($this->planId !== null) {
            DB::table('plans')->where('id', $this->planId)->delete();
        }

        parent::tearDown();
    }

    public function test_every_tenant_is_born_with_the_three_system_roles_and_the_admin_in_the_admin_one(): void
    {
        $roles = Role::query()->orderBy('id')->get();

        $this->assertSame(['Admin', 'Usuário', 'Cliente'], $roles->pluck('name')->all());
        $this->assertTrue($roles->every(fn (Role $role): bool => $role->is_system));
        $this->assertSame(TenantUserType::Admin, $this->admin->type);
        $this->assertTrue($this->admin->role?->is_system);
    }

    public function test_an_admin_registers_a_person_who_gets_a_provisional_password_for_the_right_panel(): void
    {
        Livewire::test(CreatePerson::class)
            ->fillForm(['name' => 'Bruno Lima', 'email' => 'bruno@acme.test', 'role_id' => $this->systemRoleId(TenantUserType::User)])
            ->call('create')
            ->assertHasNoFormErrors();

        $bruno = TenantUser::query()->with('role')->where('email', 'bruno@acme.test')->firstOrFail();

        $this->assertSame(TenantUserType::User, $bruno->type);
        $this->assertTrue($bruno->is_active);
        $this->assertTrue($bruno->must_change_password);
        $this->assertEqualsWithDelta(24 * 3600, now()->diffInSeconds($bruno->password_expires_at), 60);

        // A senha vai por e-mail, com o link do painel do tipo da pessoa; no banco só existe o hash.
        Notification::assertSentTo($bruno, ProvisionalPasswordNotification::class, function (ProvisionalPasswordNotification $notification) use ($bruno): bool {
            return Hash::check($notification->plainPassword, (string) $bruno->getAuthPassword())
                && $notification->loginUrl === "http://{$this->userHost}/app/login";
        });

        // Reenvio pelo admin do tenant, enquanto a pessoa não entrou.
        Livewire::test(ListPeople::class)->callTableAction('resendProvisionalPassword', $bruno)->assertNotified();

        Notification::assertSentToTimes($bruno, ProvisionalPasswordNotification::class, 2);
        $this->assertNotSame($bruno->getAuthPassword(), $bruno->fresh()?->getAuthPassword(), 'A senha anterior deixa de valer.');

        // Depois do primeiro acesso, o reenvio não é mais oferecido.
        $bruno->update(['must_change_password' => false, 'password_expires_at' => null]);
        Livewire::test(ListPeople::class)->assertTableActionHidden('resendProvisionalPassword', $bruno);
    }

    public function test_emails_are_unique_and_customers_are_not_registered_here(): void
    {
        Livewire::test(CreatePerson::class)
            ->fillForm(['name' => 'Outra Ana', 'email' => $this->admin->email, 'role_id' => $this->systemRoleId(TenantUserType::User)])
            ->call('create')
            ->assertHasFormErrors(['email']);

        $this->assertSame(1, TenantUser::query()->count());

        $this->expectExceptionObject(TenantUserRuleException::customerRole());

        app(CreateTenantUserAction::class)->execute(
            new CreateTenantUserDTO('Cliente', 'cliente@acme.test', $this->systemRoleId(TenantUserType::Customer)),
        );
    }

    public function test_a_custom_role_limits_what_an_admin_can_reach(): void
    {
        Livewire::test(CreateRole::class)
            ->fillForm(['name' => 'Gestor de perfis', 'base_type' => 'admin'])
            ->fillForm(['permissions' => ['roles.manage']])
            ->call('create')
            ->assertHasNoFormErrors();

        $role = Role::query()->where('name', 'Gestor de perfis')->firstOrFail();

        $this->assertFalse($role->is_system);
        $this->assertSame(TenantUserType::Admin, $role->base_type);
        $this->assertSame(['roles.manage'], $role->permissions);

        $carla = $this->person('Carla Dias', 'carla@acme.test', $role->id);

        $this->actAs($carla);

        Livewire::test(ListRoles::class)->assertSuccessful();
        Livewire::test(ListPeople::class)->assertForbidden();
        Livewire::test(CreatePerson::class)->assertForbidden();
    }

    public function test_the_tenant_never_loses_its_last_people_manager(): void
    {
        // Sozinho, o admin não se desativa nem deixa de gerenciar pessoas.
        Livewire::test(ListPeople::class)->callTableAction('deactivate', $this->admin)->assertNotified();
        $this->assertTrue($this->admin->fresh()?->is_active);

        Livewire::test(EditPerson::class, ['record' => $this->admin->id])
            ->fillForm(['role_id' => $this->systemRoleId(TenantUserType::User)])
            ->call('save')
            ->assertNotified();
        $this->assertSame(TenantUserType::Admin, $this->admin->fresh()?->type);

        // Com um segundo gestor, o primeiro pode ser desativado por ele, mas não o contrário em seguida.
        $dora = $this->person('Dora Reis', 'dora@acme.test', $this->systemRoleId(TenantUserType::Admin));

        $this->actAs($dora);
        Livewire::test(ListPeople::class)->callTableAction('deactivate', $this->admin);
        $this->assertFalse($this->admin->fresh()?->is_active);

        $this->actAs($this->admin->refresh());
        Livewire::test(ListPeople::class)->assertForbidden();
    }

    public function test_changing_the_type_of_a_role_moves_its_people_and_is_refused_if_nobody_would_manage_people(): void
    {
        $roleId = Role::query()->create(['name' => 'Coordenação', 'base_type' => 'admin', 'permissions' => ['people.manage', 'roles.manage']])->id;

        // O único gestor passa para o perfil customizado: continua gestor.
        Livewire::test(EditPerson::class, ['record' => $this->admin->id])
            ->fillForm(['role_id' => $roleId])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->actAs($this->admin->refresh());

        // Trocar o tipo desse perfil tiraria a gestão de pessoas do tenant: recusado.
        Livewire::test(EditRole::class, ['record' => $roleId])
            ->assertFormSet(['base_type' => 'admin', 'permissions' => ['people.manage', 'roles.manage']])
            ->fillForm(['base_type' => 'user'])
            ->call('save')
            ->assertNotified();
        $this->assertSame(TenantUserType::Admin, Role::query()->findOrFail($roleId)->base_type);

        // Com outro gestor no tenant, a troca passa: as pessoas do perfil mudam de painel
        // e as permissões que não existem para o novo tipo são descartadas.
        $this->person('Dora Reis', 'dora@acme.test', $this->systemRoleId(TenantUserType::Admin));

        Livewire::test(EditRole::class, ['record' => $roleId])
            ->fillForm(['base_type' => 'user'])
            ->call('save')
            ->assertHasNoFormErrors();

        $role = Role::query()->findOrFail($roleId);

        $this->assertSame(TenantUserType::User, $role->base_type);
        $this->assertSame([], $role->permissions);
        $this->assertSame(TenantUserType::User, $this->admin->fresh()?->type);
    }

    public function test_a_deactivated_person_is_locked_out_until_reactivated(): void
    {
        $bruno = $this->person('Bruno Lima', 'bruno@acme.test', $this->systemRoleId(TenantUserType::User));

        $this->visit($bruno, "http://{$this->userHost}/app")->assertOk();

        $this->actAs($this->admin);
        Livewire::test(ListPeople::class)->callTableAction('deactivate', $bruno);

        $this->visit($bruno->refresh(), "http://{$this->userHost}/app")->assertForbidden();

        $this->actAs($this->admin);
        Livewire::test(ListPeople::class)->callTableAction('activate', $bruno);

        $this->visit($bruno->refresh(), "http://{$this->userHost}/app")->assertOk();
    }

    public function test_system_roles_and_roles_in_use_are_protected(): void
    {
        $system = Role::query()->where('is_system', true)->where('base_type', 'admin')->firstOrFail();
        $custom = Role::query()->create(['name' => 'Atendimento', 'base_type' => 'user', 'permissions' => []]);
        $bruno = $this->person('Bruno Lima', 'bruno@acme.test', $custom->id);

        Livewire::test(ListRoles::class)
            ->assertTableActionHidden('edit', $system)
            ->assertTableActionHidden('delete', $system)
            ->assertTableActionVisible('delete', $custom)
            ->callTableAction('delete', $custom)
            ->assertNotified();

        $this->assertNotNull(Role::query()->find($custom->id), 'Perfil com pessoas não é excluído.');

        $bruno->update(['role_id' => $this->systemRoleId(TenantUserType::User)]);

        Livewire::test(ListRoles::class)->callTableAction('delete', $custom);

        $this->assertNull(Role::query()->find($custom->id));
    }

    public function test_the_people_and_roles_screens_open_in_the_admin_panel(): void
    {
        $this->visit($this->admin, "http://{$this->adminHost}/admin/pessoas")->assertOk()->assertSee($this->admin->email);
        $this->visit($this->admin, "http://{$this->adminHost}/admin/perfis")->assertOk()->assertSee('Sistema');
    }

    /** Pessoa já com o primeiro acesso feito, criada direto no banco do tenant. */
    private function person(string $name, string $email, int $roleId): TenantUser
    {
        return TenantUser::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'SenhaDeTeste123',
            'role_id' => $roleId,
        ])->load('role');
    }

    private function systemRoleId(TenantUserType $type): int
    {
        return (int) Role::query()->where('is_system', true)->where('base_type', $type->value)->valueOrFail('id');
    }

    /** Coloca o teste dentro do painel admin do tenant, como a pessoa indicada. */
    private function actAs(TenantUser $person): void
    {
        $this->enterTenant();
        $this->flushSession();
        $this->actingAs($person, 'tenant');
    }

    private function enterTenant(): void
    {
        if (! tenancy()->initialized) {
            tenancy()->initialize($this->tenant);
        }

        Filament::setCurrentPanel(Filament::getPanel(DomainPanel::Admin->panelId()));
    }

    private function leaveTenant(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
    }

    /** Requisição de verdade, resolvida pelo domínio, como a pessoa indicada. */
    private function visit(TenantUser $person, string $url): TestResponse
    {
        $this->leaveTenant();
        $this->flushSession();

        $response = $this->actingAs($person, 'tenant')->get($url);

        $this->leaveTenant();

        return $response;
    }
}
