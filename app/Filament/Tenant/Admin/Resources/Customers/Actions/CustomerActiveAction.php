<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Customers\Actions;

use App\Actions\Customer\SetCustomerActiveAction;
use App\Exceptions\DomainException;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class CustomerActiveAction
{
    public static function deactivate(): Action
    {
        return Action::make('deactivate')
            ->label('Desativar')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('O cliente deixa de entrar no portal. Nada é apagado, e ele pode ser reativado.')
            ->authorize('deactivate')
            // Dica de interface; quem decide é o Service.
            ->visible(fn (Customer $record): bool => $record->is_active)
            ->action(fn (Customer $record) => self::apply($record, false, "{$record->name} foi desativado."));
    }

    public static function activate(): Action
    {
        return Action::make('activate')
            ->label('Reativar')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->authorize('activate')
            // Dica de interface; quem decide é o Service.
            ->visible(fn (Customer $record): bool => ! $record->is_active)
            ->action(fn (Customer $record) => self::apply($record, true, "{$record->name} foi reativado."));
    }

    private static function apply(Customer $record, bool $active, string $message): void
    {
        try {
            app(SetCustomerActiveAction::class)->execute($record->id, $active);
            Notification::make()->title($message)->success()->send();
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
