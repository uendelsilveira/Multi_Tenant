<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use Illuminate\Foundation\Events\Dispatchable;

final class TenantRegistered
{
    use Dispatchable;

    public function __construct(
        public readonly string $tenantId,
    ) {}
}
