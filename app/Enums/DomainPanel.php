<?php

declare(strict_types=1);

namespace App\Enums;

enum DomainPanel: string
{
    case Admin = 'admin';
    case User = 'user';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::User => 'Usuário',
            self::Customer => 'Cliente',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
