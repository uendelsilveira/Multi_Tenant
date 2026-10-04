<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Actions;

use App\Actions\Tenant\RestoreTenantAction;
use App\Exceptions\DomainException;
use App\Models\Tenant;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;

final class TenantRestoreAction
{
    public static function make(): RestoreAction
    {
        return RestoreAction::make()
            ->using(function (Tenant $record): bool {
                try {
                    app(RestoreTenantAction::class)->execute($record->id);

                    return true;
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return false;
                }
            });
    }
}
