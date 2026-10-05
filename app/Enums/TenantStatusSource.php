<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * De onde veio uma mudança de situação do tenant.
 */
enum TenantStatusSource: string
{
    case Manual = 'manual';
    case Gateway = 'gateway';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Gateway => 'Cobrança automática',
        };
    }
}
