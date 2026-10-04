<?php

declare(strict_types=1);

namespace App\Actions\Plan;

use App\Actions\BaseAction;
use App\DTOs\Plan\CreatePlanDTO;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Support\Facades\Log;

final class CreatePlanAction extends BaseAction
{
    public function __construct(
        private readonly PlanService $service,
    ) {}

    public function execute(CreatePlanDTO $dto): Plan
    {
        $plan = $this->service->create($dto);

        Log::info('plan.created', ['plan_id' => $plan->id]);

        return $plan;
    }
}
