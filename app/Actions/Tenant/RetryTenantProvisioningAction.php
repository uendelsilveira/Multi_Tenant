<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\BaseAction;
use App\Events\Tenant\TenantProvisioningRetryRequested;
use App\Services\TenantProvisioningService;
use Illuminate\Support\Facades\Log;

final class RetryTenantProvisioningAction extends BaseAction
{
    public function __construct(
        private readonly TenantProvisioningService $service,
    ) {}

    public function execute(string $tenantId): void
    {
        $this->service->assertCanRetry($tenantId);

        event(new TenantProvisioningRetryRequested($tenantId));

        Log::info('tenant.provisioning_retry_requested', ['tenant_id' => $tenantId]);
    }
}
