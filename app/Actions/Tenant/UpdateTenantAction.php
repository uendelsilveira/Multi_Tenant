<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\BaseAction;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Events\Tenant\TenantPlanChanged;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\Log;

final class UpdateTenantAction extends BaseAction
{
    public function __construct(
        private readonly TenantService $service,
    ) {}

    public function execute(UpdateTenantDTO $dto): Tenant
    {
        $previousPlanId = $this->service->planIdOf($dto->tenantId);
        $previousCycle = $this->service->billingCycleOf($dto->tenantId);

        $tenant = $this->service->update($dto);

        Log::info('tenant.updated', ['tenant_id' => $tenant->id]);

        if ($previousPlanId !== $tenant->plan_id || $previousCycle !== $tenant->billing_cycle) {
            // A troca vale na hora: as funcionalidades do tenant são relidas do plano a cada requisição.
            // O mesmo evento leva o novo valor às próximas cobranças, também quando só o ciclo muda.
            event(new TenantPlanChanged($tenant->id, $previousPlanId, $tenant->plan_id));

            Log::info('tenant.plan_changed', [
                'tenant_id' => $tenant->id,
                'from_plan_id' => $previousPlanId,
                'to_plan_id' => $tenant->plan_id,
            ]);
        }

        return $tenant;
    }
}
