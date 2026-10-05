<?php

declare(strict_types=1);

namespace App\Listeners\Tenant;

use App\Events\Tenant\TenantProvisioningRetryRequested;
use App\Events\Tenant\TenantRegistered;
use App\Jobs\ProvisionTenantJob;

final class DispatchTenantProvisioning
{
    public function handle(TenantRegistered|TenantProvisioningRetryRequested $event): void
    {
        ProvisionTenantJob::dispatch($event->tenantId);
    }
}
