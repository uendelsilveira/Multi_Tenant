<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\People\Actions;

use App\Actions\TenantUser\RequestTenantUserProvisionalPasswordAction;
use App\Exceptions\DomainException;
use App\Models\TenantUser;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class PersonResendPasswordAction
{
    public static function make(): Action
    {
        return Action::make('resendProvisionalPassword')
            ->label('Reenviar senha provisória')
            ->icon(Heroicon::OutlinedEnvelope)
            ->requiresConfirmation()
            ->modalDescription('Uma nova senha provisória é gerada e enviada ao e-mail da pessoa. A anterior deixa de valer. Só é possível enquanto ela ainda não fez o primeiro acesso.')
            ->authorize('resendProvisionalPassword')
            // Dica de interface; quem decide é o Service.
            ->visible(fn (TenantUser $record): bool => $record->is_active && $record->must_change_password)
            ->action(function (TenantUser $record): void {
                try {
                    app(RequestTenantUserProvisionalPasswordAction::class)->execute($record->id);
                    Notification::make()->title('Nova senha provisória a caminho.')->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
