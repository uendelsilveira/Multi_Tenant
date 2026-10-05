<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\DTOs\Plan\UpdatePlanDTO;
use App\Enums\BillingCycle;
use App\Models\Plan;
use App\Models\Tenant;
use App\Repositories\Contracts\PlanRepositoryInterface;

final class PlanRepository implements PlanRepositoryInterface
{
    public function create(CreatePlanDTO $dto): Plan
    {
        return (new Plan)->getConnection()->transaction(function () use ($dto): Plan {
            $plan = Plan::query()->create([
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => $dto->isActive,
            ]);

            $this->syncPrices($plan, $dto->prices);
            $plan->features()->sync($dto->featureIds);

            return $plan->load('prices');
        });
    }

    public function update(Plan $plan, UpdatePlanDTO $dto): Plan
    {
        return $plan->getConnection()->transaction(function () use ($plan, $dto): Plan {
            $plan->update([
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => $dto->isActive,
            ]);

            $this->syncPrices($plan, $dto->prices);
            $plan->features()->sync($dto->featureIds);

            return $plan->load('prices');
        });
    }

    public function delete(Plan $plan): void
    {
        $plan->delete();
    }

    public function find(int $id): ?Plan
    {
        return Plan::query()->with('prices')->find($id);
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        return Plan::query()
            ->where('name', $name)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function setStripeProductId(Plan $plan, string $productId): void
    {
        $plan->forceFill(['stripe_product_id' => $productId])->save();
    }

    public function hasTenants(int $planId): bool
    {
        return Tenant::withTrashed()->where('plan_id', $planId)->exists();
    }

    public function cyclesInUse(int $planId): array
    {
        $cycles = [];

        $values = Tenant::withTrashed()
            ->where('plan_id', $planId)
            ->whereNotNull('billing_cycle')
            ->distinct()
            ->toBase()
            ->pluck('billing_cycle');

        foreach ($values as $value) {
            $cycles[] = BillingCycle::from((string) $value);
        }

        return $cycles;
    }

    public function selectableOptions(?int $includingId = null): array
    {
        $options = [];

        $plans = Plan::query()
            ->where(function ($query) use ($includingId): void {
                $query->where('is_active', true);

                if ($includingId !== null) {
                    $query->orWhere('id', $includingId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        foreach ($plans as $plan) {
            $options[$plan->id] = $plan->name;
        }

        return $options;
    }

    /** @param list<PlanPriceDTO> $prices */
    private function syncPrices(Plan $plan, array $prices): void
    {
        $cycles = array_map(fn (PlanPriceDTO $price): string => $price->cycle->value, $prices);

        $plan->prices()->whereNotIn('billing_cycle', $cycles)->delete();

        foreach ($prices as $price) {
            $plan->prices()->updateOrCreate(
                ['billing_cycle' => $price->cycle->value],
                ['price' => $price->price],
            );
        }
    }
}
