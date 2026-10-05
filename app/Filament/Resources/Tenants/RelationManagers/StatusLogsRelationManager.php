<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Enums\TenantStatus;
use App\Enums\TenantStatusSource;
use App\Models\TenantStatusLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Histórico de situação do tenant (RF07). Só consulta.
 */
final class StatusLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'statusLogs';

    protected static ?string $title = 'Histórico de situação';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('change')
                    ->label('Mudança')
                    ->state(fn (TenantStatusLog $record): string => $record->from_status->label().' → '.$record->to_status->label()),
                TextColumn::make('to_status')
                    ->label('Ficou')
                    ->badge()
                    ->formatStateUsing(fn (?TenantStatus $state): ?string => $state?->label())
                    ->color(fn (?TenantStatus $state): string => $state === TenantStatus::Active ? 'success' : 'warning'),
                TextColumn::make('source')
                    ->label('Origem')
                    ->formatStateUsing(fn (?TenantStatusSource $state): ?string => $state?->label()),
                TextColumn::make('centralUser.name')
                    ->label('Por')
                    ->placeholder('—'),
                TextColumn::make('reason')
                    ->label('Motivo')
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('locked_until')
                    ->label('Travado até')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ]);
    }
}
