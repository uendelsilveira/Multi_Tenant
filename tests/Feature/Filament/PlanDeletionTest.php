<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\Enums\BillingCycle;
use App\Enums\UserRole;
use App\Filament\Resources\Plans\Pages\ListPlans;
use App\Models\User;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class PlanDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_plan_without_tenants_is_deleted_with_its_prices(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $plan = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO('Descartável', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '10.00'),
        ], []));

        Livewire::test(ListPlans::class)->callTableAction('delete', $plan);

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
        $this->assertDatabaseMissing('plan_prices', ['plan_id' => $plan->id]);
    }
}
