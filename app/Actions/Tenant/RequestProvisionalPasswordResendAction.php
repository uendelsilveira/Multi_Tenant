<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\BaseAction;
use App\Events\Tenant\ProvisionalPasswordResendRequested;
use App\Services\TenantProvisioningService;
use Illuminate\Support\Facades\Log;

final class RequestProvisionalPasswordResendAction extends BaseAction
{
    public function __construct(
        private readonly TenantProvisioningService $service,
    ) {}

    public function execute(string $tenantId): void
    {
        $this->service->assertCanResendPassword($tenantId);

        event(new ProvisionalPasswordResendRequested($tenantId));

        Log::info('tenant.provisional_password_resend_requested', ['tenant_id' => $tenantId]);
    }
}
