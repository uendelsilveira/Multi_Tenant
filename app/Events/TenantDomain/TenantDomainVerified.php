<?php

declare(strict_types=1);

namespace App\Events\TenantDomain;

use Illuminate\Foundation\Events\Dispatchable;

final class TenantDomainVerified
{
    use Dispatchable;

    public function __construct(
        public readonly int $domainId,
        public readonly string $tenantId,
    ) {}
}
