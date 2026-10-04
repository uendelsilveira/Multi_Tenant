<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\Enums\BillingCycle;
use App\Enums\UserRole;
use App\Filament\Resources\Plans\Pages\CreatePlan;
use App\Filament\Resources\Plans\Pages\EditPlan;
use App\Filament\Resources\Plans\Pages\ListPlans;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Stancl\Tenancy\Events\TenantCreated;
use Tests\TestCase;

final class PlanResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([TenantCreated::class]);
    }

    public function test_an_admin_creates_a_plan_with_prices_per_cycle(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(CreatePlan::class)
            ->fillForm([
                'name' => 'Profissional',
                'description' => 'Plano completo',
                'is_active' => true,
                'prices' => ['monthly' => '99.90', 'semiannual' => null, 'annual' => '999'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('plans', ['name' => 'Profissional']);
        $this->assertDatabaseHas('plan_prices', ['billing_cycle' => 'monthly', 'price' => '99.90']);
        $this->assertDatabaseHas('plan_prices', ['billing_cycle' => 'annual', 'price' => '999.00']);
        $this->assertDatabaseMissing('plan_prices', ['billing_cycle' => 'semiannual']);
    }

    public function test_a_plan_without_any_price_is_refused(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

        Livewire::test(CreatePlan::class)
            ->fillForm(['name' => 'Vazio'])
            ->call('create')
            ->assertNotified();

        $this->assertDatabaseCount('plans', 0);
    }

    public function test_the_edit_form_is_filled_with_the_plan_prices(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $plan = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []));

        Livewire::test(EditPlan::class, ['record' => $plan->id])
            ->assertFormSet(['name' => 'Profissional', 'prices.monthly' => '99.90'])
            ->fillForm(['prices' => ['monthly' => '119.90']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('plan_prices', ['plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'price' => '119.90']);
    }

    public function test_a_plan_in_use_is_not_deleted(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $plan = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []));
        Tenant::query()->create(['id' => 'acme', 'legal_name' => 'Acme', 'plan_id' => $plan->id, 'billing_cycle' => 'monthly']);

        Livewire::test(ListPlans::class)
            ->callTableAction('delete', $plan)
            ->assertNotified();

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    public function test_an_operator_can_list_but_not_create_plans(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Operator]));

        Livewire::test(ListPlans::class)->assertSuccessful();
        Livewire::test(CreatePlan::class)->assertForbidden();
    }
}
