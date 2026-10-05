<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Actions;

use App\Actions\Billing\RetryTenantSubscriptionAction;
use App\Exceptions\DomainException;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class TenantRetrySubscriptionAction
{
    public static function make(): Action
    {
        return Action::make('retrySubscription')
            ->label('Criar assinatura novamente')
            ->icon(Heroicon::OutlinedCreditCard)
            ->requiresConfirmation()
            ->modalDescription('Tenta de novo criar o pagador e a assinatura no gateway. Se ela já existir lá, nada é duplicado.')
            ->authorize('retrySubscription')
            // Dica de interface; quem decide é o Service.
            ->visible(fn (Tenant $record): bool => ! $record->trashed()
                && $record->subscription !== null
                && $record->subscription->gateway_subscription_id === null)
            ->action(function (Tenant $record): void {
                try {
                    app(RetryTenantSubscriptionAction::class)->execute($record->id);
                    Notification::make()->title('Criação da assinatura colocada na fila.')->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
