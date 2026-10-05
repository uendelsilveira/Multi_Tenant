<?php

declare(strict_types=1);

namespace App\Enums;

enum ProvisioningStatus: string
{
    case Pending = 'pending';
    case Provisioning = 'provisioning';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Aguardando',
            self::Provisioning => 'Provisionando',
            self::Ready => 'Pronto',
            self::Failed => 'Falhou',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Provisioning => 'info',
            self::Ready => 'success',
            self::Failed => 'danger',
        };
    }
}
