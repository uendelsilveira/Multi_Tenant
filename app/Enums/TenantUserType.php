<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Tipo base de uma pessoa do tenant (RN08). Cada tipo corresponde a um painel.
 */
enum TenantUserType: string
{
    case Admin = 'admin';
    case User = 'user';
    case Customer = 'customer';

    public function label(): string
    {
        return $this->panel()->label();
    }

    public function panel(): DomainPanel
    {
        return match ($this) {
            self::Admin => DomainPanel::Admin,
            self::User => DomainPanel::User,
            self::Customer => DomainPanel::Customer,
        };
    }
}
