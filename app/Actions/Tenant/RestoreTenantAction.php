<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\BaseAction;
use App\Events\Tenant\TenantRestored;
use App\Services\TenantService;
use Illuminate\Support\Facades\Log;

final class RestoreTenantAction extends BaseAction
{
    public function __construct(
        private readonly TenantService $service,
    ) {}

    public function execute(string $tenantId): void
    {
        $this->service->restore($tenantId);

        event(new TenantRestored($tenantId));

        Log::info('tenant.restored', ['tenant_id' => $tenantId]);
    }
}
