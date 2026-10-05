<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Shared\Customers;

use App\Actions\TenantUser\RequestTenantUserProvisionalPasswordAction;
use App\Exceptions\DomainException;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class CustomerResendPasswordAction
{
    public static function make(): Action
    {
        return Action::make('resendProvisionalPassword')
            ->label('Reenviar acesso')
            ->icon(Heroicon::OutlinedEnvelope)
            ->requiresConfirmation()
            ->modalDescription('Uma nova senha provisória é enviada ao e-mail do cliente. A anterior deixa de valer. Só é possível enquanto ele ainda não fez o primeiro acesso.')
            ->authorize('resendProvisionalPassword')
            // Dica de interface; quem decide é o Service.
            ->visible(fn (Customer $record): bool => $record->is_active && $record->must_change_password)
            ->action(function (Customer $record): void {
                try {
                    app(RequestTenantUserProvisionalPasswordAction::class)->execute($record->id);
                    Notification::make()->title('Novo acesso a caminho.')->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
