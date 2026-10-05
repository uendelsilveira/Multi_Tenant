<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\BaseAction;
use App\Events\Tenant\TenantProvisioned;
use App\Services\TenantProvisioningService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

final class ProvisionTenantAction extends BaseAction
{
    public function __construct(
        private readonly TenantProvisioningService $service,
    ) {}

    public function execute(string $tenantId): void
    {
        $startedAt = microtime(true);

        $tenant = $this->service->provision($tenantId);

        event(new TenantProvisioned($tenant->id));

        $registeredAt = $tenant->getAttribute('created_at');

        // RNF03: o tempo entre cadastro e ambiente pronto é medido por estes dois valores.
        Log::info('tenant.provisioned', [
            'tenant_id' => $tenant->id,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'seconds_since_registration' => $registeredAt instanceof CarbonInterface
                ? (int) $registeredAt->diffInSeconds(now())
                : null,
        ]);
    }
}
