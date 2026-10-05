<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Actions;

use App\Actions\Tenant\ChangeTenantStatusAction;
use App\DTOs\Tenant\ChangeTenantStatusDTO;
use App\Enums\TenantStatus;
use App\Exceptions\DomainException;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class TenantChangeStatusAction
{
    public static function make(): Action
    {
        return Action::make('changeStatus')
            ->label('Alterar situação')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->authorize('changeStatus')
            ->visible(fn (Tenant $record): bool => ! $record->trashed())
            ->modalHeading(fn (Tenant $record): string => "Situação de {$record->id}")
            ->modalDescription('Suspenso, o tenant fica inteiramente bloqueado: ninguém entra em nenhum painel dele.')
            ->fillForm(fn (Tenant $record): array => ['status' => $record->status->value])
            ->schema([
                Select::make('status')
                    ->label('Situação')
                    ->options([
                        TenantStatus::Active->value => TenantStatus::Active->label(),
                        TenantStatus::Suspended->value => TenantStatus::Suspended->label(),
                    ])
                    ->required(),
                Textarea::make('reason')
                    ->label('Motivo')
                    ->helperText('Interno: fica no histórico e não é mostrado ao tenant.')
                    ->required()
                    ->rows(3),
                DateTimePicker::make('locked_until')
                    ->label('Travar até')
                    ->helperText('Opcional. Até essa data, a cobrança automática não altera a situação.')
                    ->seconds(false),
            ])
            ->action(function (Tenant $record, array $data): void {
                try {
                    app(ChangeTenantStatusAction::class)->execute(
                        ChangeTenantStatusDTO::fromArray($record->id, (int) Filament::auth()->id(), $data),
                    );
                    Notification::make()->title('Situação alterada.')->success()->send();
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
