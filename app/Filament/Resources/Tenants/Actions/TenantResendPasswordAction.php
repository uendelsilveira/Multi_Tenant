<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Actions;

use App\Actions\Tenant\RequestProvisionalPasswordResendAction;
use App\Enums\ProvisioningStatus;
use App\Exceptions\DomainException;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class TenantResendPasswordAction
{
    public static function make(): Action
    {
        return Action::make('resendProvisionalPassword')
            ->label('Reenviar senha provisória')
            ->icon(Heroicon::OutlinedEnvelope)
            ->requiresConfirmation()
            ->modalDescription('Uma nova senha provisória é gerada e enviada ao e-mail de contato. A anterior deixa de valer. Só é possível enquanto o admin ainda não fez o primeiro acesso.')
            ->authorize('resendProvisionalPassword')
            // Dica de interface; quem decide é o Service.
            ->visible(fn (Tenant $record): bool => ! $record->trashed() && $record->provisioning_status === ProvisioningStatus::Ready)
            ->action(function (Tenant $record): void {
                try {
                    app(RequestProvisionalPasswordResendAction::class)->execute($record->id);
                    Notification::make()->title('Nova senha provisória a caminho.')->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
