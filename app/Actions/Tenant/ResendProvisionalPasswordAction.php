<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\BaseAction;
use App\Services\TenantProvisioningService;
use Illuminate\Support\Facades\Log;

final class ResendProvisionalPasswordAction extends BaseAction
{
    public function __construct(
        private readonly TenantProvisioningService $service,
    ) {}

    public function execute(string $tenantId): void
    {
        $this->service->resendProvisionalPassword($tenantId);

        Log::info('tenant.provisional_password_resent', ['tenant_id' => $tenantId]);
    }
}
