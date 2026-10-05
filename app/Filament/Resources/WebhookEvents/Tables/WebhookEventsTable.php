<?php

declare(strict_types=1);

namespace App\Filament\Resources\WebhookEvents\Tables;

use App\Enums\PaymentGateway;
use App\Enums\WebhookOutcome;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class WebhookEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Recebido em')
                    ->dateTime('d/m/Y H:i:s'),
                TextColumn::make('gateway')
                    ->label('Gateway')
                    ->formatStateUsing(fn (?PaymentGateway $state): ?string => $state?->label()),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->searchable(),
                TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('outcome')
                    ->label('Resultado')
                    ->badge()
                    ->formatStateUsing(fn (?WebhookOutcome $state): string => $state?->label() ?? 'Aguardando processamento')
                    ->color(fn (?WebhookOutcome $state): string => match ($state) {
                        WebhookOutcome::Applied => 'success',
                        WebhookOutcome::Unmatched => 'warning',
                        default => 'gray',
                    })
                    ->default('—'),
                TextColumn::make('gateway_event_id')
                    ->label('Id no gateway')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
            ])
            ->filters([
                SelectFilter::make('gateway')
                    ->label('Gateway')
                    ->options(PaymentGateway::options()),
            ]);
    }
}
