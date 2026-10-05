<?php

declare(strict_types=1);

namespace App\Listeners\TenantUser;

use App\Events\TenantUser\TenantUserAccessRequested;
use App\Jobs\IssueTenantUserProvisionalPasswordJob;

final class DispatchProvisionalPasswordIssue
{
    public function handle(TenantUserAccessRequested $event): void
    {
        IssueTenantUserProvisionalPasswordJob::dispatch($event->userId);
    }
}
