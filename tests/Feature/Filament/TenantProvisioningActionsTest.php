<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Actions\Tenant\CreateTenantAction;
use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\Enums\BillingCycle;
use App\Enums\ProvisioningStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Jobs\ProvisionTenantJob;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use RuntimeException;
use Tests\Support\TenantData;
use Tests\TestCase;

final class TenantProvisioningActionsTest extends TestCase
{
    use RefreshDatabase;

    private int $planId;

    protected function setUp(): void
    {
        parent::setUp();

        // A fila é falsa: nenhum banco de tenant é criado nestes testes.
        Queue::fake();

        $this->planId = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []))->id;
    }

    public function test_registering_a_tenant_queues_its_provisioning_and_leaves_it_pending(): void
    {
        $tenant = app(CreateTenantAction::class)->execute(TenantData::create(planId: $this->planId));

        $this->assertSame(ProvisioningStatus::Pending, $tenant->refresh()->provisioning_status);
        Queue::assertPushed(ProvisionTenantJob::class, fn (ProvisionTenantJob $job): bool => $job->tenantId === 'acme');
    }

    public function test_a_job_that_gives_up_marks_the_tenant_as_failed_with_the_reason(): void
    {
        $this->tenant(ProvisioningStatus::Provisioning);

        (new ProvisionTenantJob('acme'))->failed(new RuntimeException('Servidor de banco indisponível.'));

        $this->assertDatabaseHas('tenants', [
            'id' => 'acme',
            'provisioning_status' => 'failed',
            'provisioning_error' => 'Servidor de banco indisponível.',
        ]);
    }

    public function test_an_admin_requeues_a_failed_provisioning(): void
    {
        $tenant = $this->tenant(ProvisioningStatus::Failed);
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(ListTenants::class)
            ->callTableAction('retryProvisioning', $tenant)
            ->assertNotified();

        Queue::assertPushed(ProvisionTenantJob::class, fn (ProvisionTenantJob $job): bool => $job->tenantId === 'acme');
    }

    public function test_retry_is_not_offered_for_a_ready_tenant_and_resend_is_not_offered_before_that(): void
    {
        $tenant = $this->tenant(ProvisioningStatus::Ready);
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(ListTenants::class)
            ->assertTableActionHidden('retryProvisioning', $tenant)
            ->assertTableActionVisible('resendProvisionalPassword', $tenant);

        app(TenantRepositoryInterface::class)->updateProvisioning($tenant, ProvisioningStatus::Failed, 'erro');

        Livewire::test(ListTenants::class)
            ->assertTableActionVisible('retryProvisioning', $tenant)
            ->assertTableActionHidden('resendProvisionalPassword', $tenant);
    }

    public function test_an_operator_cannot_requeue_or_resend(): void
    {
        $tenant = $this->tenant(ProvisioningStatus::Failed);
        $this->actingAs(User::factory()->create(['role' => UserRole::Operator]));

        Livewire::test(ListTenants::class)
            ->assertTableActionHidden('retryProvisioning', $tenant)
            ->assertTableActionHidden('resendProvisionalPassword', $tenant);

        Queue::assertNothingPushed();
    }

    private function tenant(ProvisioningStatus $status): Tenant
    {
        $tenants = app(TenantRepositoryInterface::class);
        $tenant = $tenants->create(TenantData::create(planId: $this->planId));
        $tenants->updateProvisioning($tenant, $status, $status === ProvisioningStatus::Failed ? 'erro' : null);

        return $tenant;
    }
}
