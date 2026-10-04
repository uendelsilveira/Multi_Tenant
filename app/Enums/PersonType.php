<?php

declare(strict_types=1);

namespace App\Enums;

enum PersonType: string
{
    case Company = 'pj';
    case Individual = 'pf';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Pessoa jurídica',
            self::Individual => 'Pessoa física',
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
