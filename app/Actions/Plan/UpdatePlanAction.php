<?php

declare(strict_types=1);

namespace App\Actions\Plan;

use App\Actions\BaseAction;
use App\DTOs\Plan\UpdatePlanDTO;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Support\Facades\Log;

final class UpdatePlanAction extends BaseAction
{
    public function __construct(
        private readonly PlanService $service,
    ) {}

    public function execute(UpdatePlanDTO $dto): Plan
    {
        $plan = $this->service->update($dto);

        Log::info('plan.updated', ['plan_id' => $plan->id]);

        return $plan;
    }
}
