<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\UpdatePlanDTO;
use App\Enums\BillingCycle;
use App\Models\Plan;

interface PlanRepositoryInterface
{
    public function create(CreatePlanDTO $dto): Plan;

    public function update(Plan $plan, UpdatePlanDTO $dto): Plan;

    public function delete(Plan $plan): void;

    /** Carrega o plano com seus preços. */
    public function find(int $id): ?Plan;

    public function nameExists(string $name, ?int $exceptId = null): bool;

    public function hasTenants(int $planId): bool;

    public function setStripeProductId(Plan $plan, string $productId): void;

    /**
     * Ciclos do plano que têm ao menos um tenant contratado.
     *
     * @return list<BillingCycle>
     */
    public function cyclesInUse(int $planId): array;

    /**
     * Planos ativos para seleção, mais o plano indicado mesmo que inativo.
     *
     * @return array<int, string>
     */
    public function selectableOptions(?int $includingId = null): array;
}
