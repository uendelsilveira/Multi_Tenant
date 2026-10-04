<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Actions;

use App\Actions\Tenant\SoftDeleteTenantAction;
use App\Exceptions\DomainException;
use App\Models\Tenant;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;

final class TenantSoftDeleteAction
{
    public static function make(): DeleteAction
    {
        return DeleteAction::make()
            ->modalDescription('O tenant sai da listagem e seus domínios deixam de responder. O banco de dados é mantido e o tenant pode ser restaurado.')
            ->using(function (Tenant $record): bool {
                try {
                    app(SoftDeleteTenantAction::class)->execute($record->id);

                    return true;
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return false;
                }
            });
    }
}
