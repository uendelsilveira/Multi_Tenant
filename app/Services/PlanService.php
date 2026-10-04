<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\DTOs\Plan\UpdatePlanDTO;
use App\Exceptions\Plan\InvalidPlanPricesException;
use App\Exceptions\Plan\PlanAlreadyExistsException;
use App\Exceptions\Plan\PlanInUseException;
use App\Exceptions\Plan\PlanNotFoundException;
use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;

final class PlanService
{
    public function __construct(
        private readonly PlanRepositoryInterface $plans,
    ) {}

    public function create(CreatePlanDTO $dto): Plan
    {
        $this->assertValidPrices($dto->prices);

        if ($this->plans->nameExists($dto->name)) {
            throw PlanAlreadyExistsException::withName($dto->name);
        }

        return $this->plans->create($dto);
    }

    public function update(UpdatePlanDTO $dto): Plan
    {
        $plan = $this->findOrFail($dto->planId);

        $this->assertValidPrices($dto->prices);

        if ($this->plans->nameExists($dto->name, $plan->id)) {
            throw PlanAlreadyExistsException::withName($dto->name);
        }

        $offered = array_map(fn (PlanPriceDTO $price): string => $price->cycle->value, $dto->prices);

        foreach ($this->plans->cyclesInUse($plan->id) as $cycle) {
            if (! in_array($cycle->value, $offered, true)) {
                throw InvalidPlanPricesException::cycleInUse($cycle);
            }
        }

        return $this->plans->update($plan, $dto);
    }

    public function delete(int $planId): void
    {
        $plan = $this->findOrFail($planId);

        if ($this->plans->hasTenants($plan->id)) {
            throw PlanInUseException::byTenants($plan->name);
        }

        $this->plans->delete($plan);
    }

    /** @return array<int, string> */
    public function selectableOptions(?int $includingId = null): array
    {
        return $this->plans->selectableOptions($includingId);
    }

    private function findOrFail(int $planId): Plan
    {
        return $this->plans->find($planId) ?? throw PlanNotFoundException::withId($planId);
    }

    /** @param list<PlanPriceDTO> $prices */
    private function assertValidPrices(array $prices): void
    {
        if ($prices === []) {
            throw InvalidPlanPricesException::none();
        }

        $seen = [];

        foreach ($prices as $price) {
            if (in_array($price->cycle, $seen, true)) {
                throw InvalidPlanPricesException::duplicatedCycle($price->cycle);
            }

            if (! is_numeric($price->price) || (float) $price->price < 0) {
                throw InvalidPlanPricesException::invalidAmount($price->cycle);
            }

            $seen[] = $price->cycle;
        }
    }
}
