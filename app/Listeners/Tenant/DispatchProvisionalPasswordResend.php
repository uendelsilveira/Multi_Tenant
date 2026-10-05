<?php

declare(strict_types=1);

namespace App\Listeners\Tenant;

use App\Events\Tenant\ProvisionalPasswordResendRequested;
use App\Jobs\ResendProvisionalPasswordJob;

final class DispatchProvisionalPasswordResend
{
    public function handle(ProvisionalPasswordResendRequested $event): void
    {
        ResendProvisionalPasswordJob::dispatch($event->tenantId);
    }
}
