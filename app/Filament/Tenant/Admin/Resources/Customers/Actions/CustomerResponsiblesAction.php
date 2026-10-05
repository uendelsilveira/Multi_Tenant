<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Customers\Actions;

use App\Actions\Customer\SyncCustomerResponsiblesAction;
use App\Exceptions\DomainException;
use App\Models\Customer;
use App\Services\CustomerService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class CustomerResponsiblesAction
{
    public static function make(): Action
    {
        return Action::make('manageResponsibles')
            ->label('Responsáveis')
            ->icon(Heroicon::OutlinedLink)
            ->authorize('manageResponsibles')
            ->modalHeading(fn (Customer $record): string => "Quem atende {$record->name}")
            ->modalDescription('Cada usuário marcado passa a ver este cliente. Todo cliente precisa de pelo menos um responsável.')
            ->fillForm(fn (Customer $record): array => ['user_ids' => $record->responsibles->modelKeys()])
            ->schema([
                Select::make('user_ids')
                    ->label('Usuários responsáveis')
                    ->multiple()
                    ->options(fn (): array => app(CustomerService::class)->responsibleOptions())
                    ->required(),
            ])
            ->action(function (Customer $record, array $data): void {
                try {
                    app(SyncCustomerResponsiblesAction::class)->execute(
                        $record->id,
                        array_values(array_map(intval(...), (array) ($data['user_ids'] ?? []))),
                    );
                    Notification::make()->title('Responsáveis atualizados.')->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
