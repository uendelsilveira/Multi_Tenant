<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Actions;

use App\Actions\Tenant\RetryTenantProvisioningAction;
use App\Enums\ProvisioningStatus;
use App\Exceptions\DomainException;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class TenantRetryProvisioningAction
{
    public static function make(): Action
    {
        return Action::make('retryProvisioning')
            ->label('Provisionar novamente')
            ->icon(Heroicon::OutlinedArrowPath)
            ->requiresConfirmation()
            ->modalDescription('A preparação do ambiente é refeita a partir de onde parou. Nada que já foi criado é apagado.')
            ->authorize('retryProvisioning')
            // Dica de interface; quem decide é o Service.
            ->visible(fn (Tenant $record): bool => ! $record->trashed() && $record->provisioning_status !== ProvisioningStatus::Ready)
            ->action(function (Tenant $record): void {
                try {
                    app(RetryTenantProvisioningAction::class)->execute($record->id);
                    Notification::make()->title('Provisionamento colocado na fila.')->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
