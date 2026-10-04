<?php

declare(strict_types=1);

namespace App\Enums;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Semiannual = 'semiannual';
    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Mensal',
            self::Semiannual => 'Semestral',
            self::Annual => 'Anual',
        };
    }

    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Semiannual => 6,
            self::Annual => 12,
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
