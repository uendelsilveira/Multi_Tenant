<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\DTOs\Plan\UpdatePlanDTO;
use App\Enums\BillingCycle;
use App\Models\Feature;
use App\Models\Tenant;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Events\TenantCreated;
use Tests\TestCase;

final class PlanRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PlanRepositoryInterface $plans;

    protected function setUp(): void
    {
        parent::setUp();

        // Impede a criação real do banco do tenant durante os testes.
        Event::fake([TenantCreated::class]);

        $this->plans = app(PlanRepositoryInterface::class);
    }

    public function test_it_persists_the_plan_with_prices_and_features(): void
    {
        $feature = Feature::query()->create(['key' => 'helpdesk.tickets', 'name' => 'Chamados', 'module' => 'helpdesk']);

        $plan = $this->plans->create(new CreatePlanDTO('Profissional', 'Plano completo', true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
            new PlanPriceDTO(BillingCycle::Annual, '999.00'),
        ], [$feature->id]));

        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'Profissional', 'is_active' => true]);
        $this->assertDatabaseHas('plan_prices', ['plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'price' => '99.90']);
        $this->assertDatabaseHas('plan_prices', ['plan_id' => $plan->id, 'billing_cycle' => 'annual', 'price' => '999.00']);
        $this->assertDatabaseHas('feature_plan', ['plan_id' => $plan->id, 'feature_id' => $feature->id]);
    }

    public function test_updating_replaces_prices_by_cycle(): void
    {
        $plan = $this->plans->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
            new PlanPriceDTO(BillingCycle::Annual, '999.00'),
        ], []));

        $this->plans->update($plan, new UpdatePlanDTO($plan->id, 'Profissional', null, false, [
            new PlanPriceDTO(BillingCycle::Monthly, '109.90'),
            new PlanPriceDTO(BillingCycle::Semiannual, '599.00'),
        ], []));

        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'is_active' => false]);
        $this->assertDatabaseHas('plan_prices', ['plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'price' => '109.90']);
        $this->assertDatabaseHas('plan_prices', ['plan_id' => $plan->id, 'billing_cycle' => 'semiannual']);
        $this->assertDatabaseMissing('plan_prices', ['plan_id' => $plan->id, 'billing_cycle' => 'annual']);
    }

    public function test_it_reports_usage_including_deleted_tenants(): void
    {
        $plan = $this->plans->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Annual, '999.00'),
        ], []));

        $this->assertFalse($this->plans->hasTenants($plan->id));

        $tenant = Tenant::query()->create(['id' => 'acme', 'legal_name' => 'Acme', 'plan_id' => $plan->id, 'billing_cycle' => 'annual']);
        $tenant->delete();

        $this->assertTrue($this->plans->hasTenants($plan->id));
        $this->assertEquals([BillingCycle::Annual], $this->plans->cyclesInUse($plan->id));
    }

    public function test_selectable_options_hide_inactive_plans_except_the_current_one(): void
    {
        $active = $this->plans->create(new CreatePlanDTO('Ativo', null, true, [new PlanPriceDTO(BillingCycle::Monthly, '10')], []));
        $inactive = $this->plans->create(new CreatePlanDTO('Inativo', null, false, [new PlanPriceDTO(BillingCycle::Monthly, '10')], []));

        $this->assertSame([$active->id => 'Ativo'], $this->plans->selectableOptions());
        $this->assertSame([$active->id => 'Ativo', $inactive->id => 'Inativo'], $this->plans->selectableOptions($inactive->id));
    }
}
