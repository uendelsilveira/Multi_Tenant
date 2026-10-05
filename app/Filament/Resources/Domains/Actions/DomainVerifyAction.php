<?php

declare(strict_types=1);

namespace App\Filament\Resources\Domains\Actions;

use App\Actions\TenantDomain\VerifyTenantDomainAction;
use App\Enums\DomainStatus;
use App\Exceptions\DomainException;
use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class DomainVerifyAction
{
    public static function make(): Action
    {
        return Action::make('verify')
            ->label('Marcar como verificado')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Confirme só depois de conferir que o domínio aponta para a plataforma. A partir daqui ele passa a responder pelo tenant.')
            ->authorize('verify')
            // Dica de interface; quem decide é o Service.
            ->visible(fn (Domain $record): bool => $record->status === DomainStatus::Pending)
            ->action(function (Domain $record): void {
                try {
                    app(VerifyTenantDomainAction::class)->execute($record->id, (int) Filament::auth()->id());
                    Notification::make()->title("{$record->domain} verificado.")->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
