<?php

declare(strict_types=1);

namespace App\Listeners\Billing;

use App\Events\Tenant\TenantPlanChanged;
use App\Jobs\SyncSubscriptionPriceJob;

final class DispatchSubscriptionPriceSync
{
    public function handle(TenantPlanChanged $event): void
    {
        SyncSubscriptionPriceJob::dispatch($event->tenantId);
    }
}
