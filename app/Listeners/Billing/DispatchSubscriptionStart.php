<?php

declare(strict_types=1);

namespace App\Listeners\Billing;

use App\Events\Billing\TenantSubscriptionRetryRequested;
use App\Events\Tenant\TenantRegistered;
use App\Jobs\StartTenantSubscriptionJob;

final class DispatchSubscriptionStart
{
    public function handle(TenantRegistered|TenantSubscriptionRetryRequested $event): void
    {
        StartTenantSubscriptionJob::dispatch($event->tenantId);
    }
}
