<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\DTOs\Plan\UpdatePlanDTO;
use App\Enums\BillingCycle;
use App\Exceptions\Plan\InvalidPlanPricesException;
use App\Exceptions\Plan\PlanAlreadyExistsException;
use App\Exceptions\Plan\PlanInUseException;
use App\Exceptions\Plan\PlanNotFoundException;
use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Services\PlanService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class PlanServiceTest extends TestCase
{
    private PlanRepositoryInterface&MockInterface $plans;

    private PlanService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plans = Mockery::mock(PlanRepositoryInterface::class);
        $this->service = new PlanService($this->plans);
    }

    public function test_it_creates_a_plan_with_valid_prices(): void
    {
        $dto = $this->createDto();
        $plan = new Plan;

        $this->plans->shouldReceive('nameExists')->with('Profissional')->andReturn(false);
        $this->plans->shouldReceive('create')->once()->with($dto)->andReturn($plan);

        $this->assertSame($plan, $this->service->create($dto));
    }

    public function test_it_rejects_a_plan_without_prices(): void
    {
        $this->plans->shouldNotReceive('create');

        $this->expectExceptionObject(InvalidPlanPricesException::none());

        $this->service->create($this->createDto(prices: []));
    }

    public function test_it_rejects_a_negative_price(): void
    {
        $this->plans->shouldNotReceive('create');

        $this->expectExceptionObject(InvalidPlanPricesException::invalidAmount(BillingCycle::Monthly));

        $this->service->create($this->createDto(prices: [new PlanPriceDTO(BillingCycle::Monthly, '-1')]));
    }

    public function test_it_rejects_the_same_cycle_twice(): void
    {
        $this->plans->shouldNotReceive('create');

        $this->expectExceptionObject(InvalidPlanPricesException::duplicatedCycle(BillingCycle::Monthly));

        $this->service->create($this->createDto(prices: [
            new PlanPriceDTO(BillingCycle::Monthly, '10'),
            new PlanPriceDTO(BillingCycle::Monthly, '20'),
        ]));
    }

    public function test_it_rejects_a_duplicated_name(): void
    {
        $this->plans->shouldReceive('nameExists')->andReturn(true);
        $this->plans->shouldNotReceive('create');

        $this->expectException(PlanAlreadyExistsException::class);

        $this->service->create($this->createDto());
    }

    public function test_it_does_not_remove_a_cycle_that_tenants_are_using(): void
    {
        $this->plans->shouldReceive('find')->with(1)->andReturn($this->plan());
        $this->plans->shouldReceive('nameExists')->andReturn(false);
        $this->plans->shouldReceive('cyclesInUse')->with(1)->andReturn([BillingCycle::Annual]);
        $this->plans->shouldNotReceive('update');

        $this->expectExceptionObject(InvalidPlanPricesException::cycleInUse(BillingCycle::Annual));

        $this->service->update(new UpdatePlanDTO(1, 'Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []));
    }

    public function test_it_does_not_delete_a_plan_in_use(): void
    {
        $this->plans->shouldReceive('find')->with(1)->andReturn($this->plan());
        $this->plans->shouldReceive('hasTenants')->with(1)->andReturn(true);
        $this->plans->shouldNotReceive('delete');

        $this->expectException(PlanInUseException::class);

        $this->service->delete(1);
    }

    public function test_it_deletes_a_plan_without_tenants(): void
    {
        $plan = $this->plan();

        $this->plans->shouldReceive('find')->with(1)->andReturn($plan);
        $this->plans->shouldReceive('hasTenants')->with(1)->andReturn(false);
        $this->plans->shouldReceive('delete')->once()->with($plan);

        $this->service->delete(1);
    }

    public function test_it_fails_when_the_plan_does_not_exist(): void
    {
        $this->plans->shouldReceive('find')->with(99)->andReturn(null);

        $this->expectException(PlanNotFoundException::class);

        $this->service->delete(99);
    }

    /** @param list<PlanPriceDTO>|null $prices */
    private function createDto(?array $prices = null): CreatePlanDTO
    {
        return new CreatePlanDTO(
            name: 'Profissional',
            description: null,
            isActive: true,
            prices: $prices ?? [new PlanPriceDTO(BillingCycle::Monthly, '99.90')],
            featureIds: [],
        );
    }

    private function plan(): Plan
    {
        $plan = new Plan(['name' => 'Profissional', 'is_active' => true]);
        $plan->id = 1;

        return $plan;
    }
}
