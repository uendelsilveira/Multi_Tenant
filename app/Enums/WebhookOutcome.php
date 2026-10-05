<?php

declare(strict_types=1);

namespace App\Enums;

enum WebhookOutcome: string
{
    case Applied = 'applied';
    case Ignored = 'ignored';
    case Unmatched = 'unmatched';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Aplicado',
            self::Ignored => 'Ignorado (tipo sem efeito)',
            self::Unmatched => 'Sem assinatura correspondente',
        };
    }
}
