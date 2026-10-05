<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\BaseAction;
use App\Services\TenantProvisioningService;
use Illuminate\Support\Facades\Log;

final class MarkTenantProvisioningFailedAction extends BaseAction
{
    public function __construct(
        private readonly TenantProvisioningService $service,
    ) {}

    public function execute(string $tenantId, string $error): void
    {
        $this->service->markFailed($tenantId, $error);

        Log::error('tenant.provisioning_failed', ['tenant_id' => $tenantId, 'error' => $error]);
    }
}
