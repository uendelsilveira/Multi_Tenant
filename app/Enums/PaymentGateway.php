<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentGateway: string
{
    case Asaas = 'asaas';
    case Stripe = 'stripe';

    public function label(): string
    {
        return match ($this) {
            self::Asaas => 'Asaas',
            self::Stripe => 'Stripe',
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
