<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use Illuminate\Foundation\Events\Dispatchable;

final class TenantPlanChanged
{
    use Dispatchable;

    public function __construct(
        public readonly string $tenantId,
        public readonly ?int $fromPlanId,
        public readonly ?int $toPlanId,
    ) {}
}
