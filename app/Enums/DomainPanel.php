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

    /** Identificador do painel no Filament. */
    public function panelId(): string
    {
        return 'tenant-'.$this->value;
    }

    /** Caminho do painel dentro do domínio que aponta para ele (ADR-0008). */
    public function path(): string
    {
        return match ($this) {
            self::Admin => 'admin',
            self::User => 'app',
            self::Customer => 'portal',
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
