<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Situação da cobrança de um tenant. É diferente da situação do tenant: uma
 * assinatura vencida ainda não suspende ninguém, por causa da carência.
 */
enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Overdue = 'overdue';
    case Canceled = 'canceled';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Aguardando criação',
            self::Active => 'Em dia',
            self::Overdue => 'Vencida',
            self::Canceled => 'Cancelada',
            self::Failed => 'Falhou ao criar',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Active => 'success',
            self::Overdue => 'warning',
            self::Canceled, self::Failed => 'danger',
        };
    }
}
