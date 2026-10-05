<?php

declare(strict_types=1);

namespace App\DTOs\TenantDomain;

use App\Enums\DomainPanel;
use App\Models\Tenant;

final class ResolvedTenantDomainDTO
{
    public function __construct(
        public readonly Tenant $tenant,
        public readonly DomainPanel $panel,
    ) {}
}
