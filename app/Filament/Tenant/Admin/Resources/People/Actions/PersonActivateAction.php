<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\People\Actions;

use App\Actions\TenantUser\ActivateTenantUserAction;
use App\Exceptions\DomainException;
use App\Models\TenantUser;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class PersonActivateAction
{
    public static function make(): Action
    {
        return Action::make('activate')
            ->label('Reativar')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->authorize('activate')
            // Dica de interface; quem decide é o Service.
            ->visible(fn (TenantUser $record): bool => ! $record->is_active)
            ->action(function (TenantUser $record): void {
                try {
                    app(ActivateTenantUserAction::class)->execute($record->id);
                    Notification::make()->title("{$record->name} foi reativada.")->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
