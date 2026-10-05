<?php

declare(strict_types=1);

namespace App\Events\Billing;

use Illuminate\Foundation\Events\Dispatchable;

final class TenantSubscriptionRetryRequested
{
    use Dispatchable;

    public function __construct(
        public readonly string $tenantId,
    ) {}
}
