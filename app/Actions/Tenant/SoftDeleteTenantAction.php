<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\BaseAction;
use App\Events\Tenant\TenantSoftDeleted;
use App\Services\TenantService;
use Illuminate\Support\Facades\Log;

final class SoftDeleteTenantAction extends BaseAction
{
    public function __construct(
        private readonly TenantService $service,
    ) {}

    public function execute(string $tenantId): void
    {
        $this->service->softDelete($tenantId);

        event(new TenantSoftDeleted($tenantId));

        Log::info('tenant.soft_deleted', ['tenant_id' => $tenantId]);
    }
}
