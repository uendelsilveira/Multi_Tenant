<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\Tenant\CreateTenantAction;
use App\Enums\BillingCycle;
use App\Events\Tenant\TenantRegistered;
use App\Exceptions\Tenant\TenantAlreadyExistsException;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Tenant;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\DocumentValidator;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\Support\TenantData;
use Tests\TestCase;

/**
 * O Service é final e sem interface, então a Action é testada com o Service
 * real e os Repositories em mock.
 */
final class CreateTenantActionTest extends TestCase
{
    private TenantRepositoryInterface&MockInterface $tenants;

    private CreateTenantAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([TenantRegistered::class]);

        $plan = new Plan(['name' => 'Profissional', 'is_active' => true]);
        $plan->id = 1;
        $plan->setRelation('prices', new Collection([
            new PlanPrice(['billing_cycle' => BillingCycle::Monthly->value, 'price' => '99.90']),
        ]));

        $plans = Mockery::mock(PlanRepositoryInterface::class);
        $plans->shouldReceive('find')->andReturn($plan);

        $this->tenants = Mockery::mock(TenantRepositoryInterface::class);
        $this->tenants->shouldReceive('documentExists')->andReturn(false);
        $this->tenants->shouldReceive('hostsInUse')->andReturn([]);

        $this->action = new CreateTenantAction(
            new TenantService($this->tenants, $plans, new DocumentValidator, ['localhost']),
        );
    }

    public function test_it_announces_the_tenant_after_registering_it(): void
    {
        $tenant = new Tenant;
        $tenant->id = 'acme';

        $this->tenants->shouldReceive('slugExists')->andReturn(false);
        $this->tenants->shouldReceive('create')->once()->andReturn($tenant);

        $this->assertSame($tenant, $this->action->execute(TenantData::create()));

        Event::assertDispatched(TenantRegistered::class, fn (TenantRegistered $event): bool => $event->tenantId === 'acme');
    }

    public function test_it_announces_nothing_when_a_rule_is_violated(): void
    {
        $this->tenants->shouldReceive('slugExists')->andReturn(true);
        $this->tenants->shouldNotReceive('create');

        try {
            $this->action->execute(TenantData::create());
            $this->fail('A regra de slug único deveria ter barrado o cadastro.');
        } catch (TenantAlreadyExistsException) {
            Event::assertNotDispatched(TenantRegistered::class);
        }
    }
}
