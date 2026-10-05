<?php

declare(strict_types=1);

namespace App\Filament\Resources\Domains\Actions;

use App\Actions\TenantDomain\CheckTenantDomainDnsAction;
use App\Exceptions\DomainException;
use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class DomainDnsCheckAction
{
    public static function make(): Action
    {
        return Action::make('checkDns')
            ->label('Testar DNS')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->color('gray')
            ->action(function (Domain $record): void {
                try {
                    $records = app(CheckTenantDomainDnsAction::class)->execute($record->id);
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()
                    ->title("DNS de {$record->domain}")
                    ->body($records === [] ? 'Nenhum registro A, AAAA ou CNAME encontrado.' : implode("\n", $records))
                    ->color($records === [] ? 'warning' : 'info')
                    ->persistent()
                    ->send();
            });
    }
}
