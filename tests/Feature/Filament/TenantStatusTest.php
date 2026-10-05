<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\Enums\BillingCycle;
use App\Enums\ProvisioningStatus;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Events\Tenant\TenantStatusChanged;
use App\Filament\Resources\Tenants\Pages\EditTenant;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Filament\Resources\Tenants\RelationManagers\StatusLogsRelationManager;
use App\Http\Middleware\EnsureTenantIsNotSuspended;
use App\Models\Tenant;
use App\Models\TenantStatusLog;
use App\Models\User;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Support\TenantData;
use Tests\TestCase;

/**
 * Situação do tenant (RF06, RF07, RF19): o central altera com motivo, tudo
 * fica no histórico e o tenant suspenso é bloqueado por inteiro.
 */
final class TenantStatusTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $planId = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []))->id;

        $tenants = app(TenantRepositoryInterface::class);

        $this->tenant = $tenants->create(TenantData::create(planId: $planId));
        $tenants->updateProvisioning($this->tenant, ProvisioningStatus::Ready);
        $this->tenant->domains()->update(['status' => 'active']);

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_an_admin_suspends_a_tenant_and_the_change_goes_to_the_history(): void
    {
        Event::fake([TenantStatusChanged::class]);
        $this->actingAs($this->admin);

        Livewire::test(ListTenants::class)
            ->callTableAction('changeStatus', $this->tenant, data: [
                'status' => 'suspended',
                'reason' => 'Três mensalidades em aberto.',
            ])
            ->assertNotified();

        $this->assertSame(TenantStatus::Suspended, $this->tenant->refresh()->status);
        $this->assertDatabaseHas('tenant_status_logs', [
            'tenant_id' => 'acme',
            'from_status' => 'active',
            'to_status' => 'suspended',
            'source' => 'manual',
            'central_user_id' => $this->admin->id,
            'reason' => 'Três mensalidades em aberto.',
        ]);
        Event::assertDispatched(TenantStatusChanged::class, fn (TenantStatusChanged $event): bool => $event->tenantId === 'acme'
            && $event->status === TenantStatus::Suspended);
    }

    public function test_the_reason_is_required_and_nothing_changes_without_it(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListTenants::class)
            ->callTableAction('changeStatus', $this->tenant, data: ['status' => 'suspended', 'reason' => ''])
            ->assertHasTableActionErrors(['reason']);

        $this->assertSame(TenantStatus::Active, $this->tenant->refresh()->status);
        $this->assertDatabaseCount('tenant_status_logs', 0);
    }

    public function test_reactivating_with_a_lock_records_until_when_billing_is_held_off(): void
    {
        $this->actingAs($this->admin);
        $until = now()->addDays(15)->startOfMinute();

        Livewire::test(ListTenants::class)
            ->callTableAction('changeStatus', $this->tenant, data: ['status' => 'suspended', 'reason' => 'Inadimplência.']);

        Livewire::test(ListTenants::class)
            ->callTableAction('changeStatus', $this->tenant, data: [
                'status' => 'active',
                'reason' => 'Acordo: paga em 15 dias.',
                'locked_until' => $until->format('Y-m-d H:i:s'),
            ])
            ->assertNotified();

        $this->tenant->refresh();

        $this->assertSame(TenantStatus::Active, $this->tenant->status);
        $this->assertTrue($until->equalTo($this->tenant->status_locked_until));
        $this->assertSame(2, TenantStatusLog::query()->count());

        // O histórico aparece na edição do tenant, do mais recente para o mais antigo.
        Livewire::test(StatusLogsRelationManager::class, ['ownerRecord' => $this->tenant, 'pageClass' => EditTenant::class])
            ->assertSuccessful()
            ->assertCanSeeTableRecords(TenantStatusLog::query()->get())
            ->assertSee('Acordo: paga em 15 dias.')
            ->assertSee('Inadimplência.');
    }

    public function test_an_operator_sees_the_history_but_cannot_change_the_status(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Operator]));

        Livewire::test(ListTenants::class)->assertTableActionHidden('changeStatus', $this->tenant);

        Livewire::test(StatusLogsRelationManager::class, ['ownerRecord' => $this->tenant, 'pageClass' => EditTenant::class])
            ->assertSuccessful();
    }

    public function test_a_suspended_tenant_is_entirely_blocked_and_asks_to_contact_the_administrator(): void
    {
        $this->get('http://painel.acme.test/')->assertRedirect('/admin');
        tenancy()->end();

        $this->suspend();

        foreach (['/', '/admin', '/admin/login', '/admin/password-reset/request'] as $path) {
            $this->get("http://painel.acme.test{$path}")
                ->assertForbidden()
                ->assertSee('Conta suspensa')
                ->assertSee('Entre em contato com o administrador');

            tenancy()->end();
        }

        // Reativado, volta a responder na hora.
        app(TenantRepositoryInterface::class)->find('acme')?->update(['status' => 'active']);

        $this->get('http://painel.acme.test/')->assertRedirect('/admin');
    }

    public function test_the_block_also_covers_screens_already_open(): void
    {
        // Toda interação de uma tela aberta passa pela rota de atualização do Livewire.
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route): bool => in_array('POST', $route->methods(), true) && str_contains($route->uri(), 'livewire') && str_contains($route->uri(), 'update'));

        $this->assertNotNull($route);
        $this->assertContains(EnsureTenantIsNotSuspended::class, $route->gatherMiddleware());
    }

    public function test_the_central_panel_is_not_affected_by_a_suspended_tenant(): void
    {
        $this->suspend();
        $this->actingAs($this->admin);

        $this->get('http://localhost/admin/tenants')->assertOk();
    }

    private function suspend(): void
    {
        app(TenantRepositoryInterface::class)->find('acme')?->update(['status' => 'suspended']);
    }
}
