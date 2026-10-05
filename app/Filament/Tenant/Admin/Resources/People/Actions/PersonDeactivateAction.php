<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\People\Actions;

use App\Actions\TenantUser\DeactivateTenantUserAction;
use App\Exceptions\DomainException;
use App\Models\TenantUser;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class PersonDeactivateAction
{
    public static function make(): Action
    {
        return Action::make('deactivate')
            ->label('Desativar')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('A pessoa deixa de entrar no sistema. Nada do que ela fez é apagado, e ela pode ser reativada.')
            ->authorize('deactivate')
            // Dica de interface; quem decide é o Service.
            ->visible(fn (TenantUser $record): bool => $record->is_active)
            ->action(function (TenantUser $record): void {
                try {
                    app(DeactivateTenantUserAction::class)->execute($record->id, (int) Filament::auth()->id());
                    Notification::make()->title("{$record->name} foi desativada.")->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
