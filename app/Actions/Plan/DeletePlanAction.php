<?php

declare(strict_types=1);

namespace App\Actions\Plan;

use App\Actions\BaseAction;
use App\Services\PlanService;
use Illuminate\Support\Facades\Log;

final class DeletePlanAction extends BaseAction
{
    public function __construct(
        private readonly PlanService $service,
    ) {}

    public function execute(int $planId): void
    {
        $this->service->delete($planId);

        Log::info('plan.deleted', ['plan_id' => $planId]);
    }
}
