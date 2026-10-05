<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Enums\TenantStatus;
use App\Enums\TenantStatusSource;
use Illuminate\Foundation\Events\Dispatchable;

final class TenantStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly string $tenantId,
        public readonly TenantStatus $status,
        public readonly TenantStatusSource $source,
    ) {}
}
