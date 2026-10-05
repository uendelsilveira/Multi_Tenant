<?php

declare(strict_types=1);

namespace App\Events\TenantUser;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Emitido dentro do contexto de um tenant: a pessoa precisa de uma senha provisória.
 */
final class TenantUserAccessRequested
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
    ) {}
}
