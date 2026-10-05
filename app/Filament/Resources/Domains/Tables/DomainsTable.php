<?php

declare(strict_types=1);

namespace App\Filament\Resources\Domains\Tables;

use App\Enums\DomainPanel;
use App\Enums\DomainStatus;
use App\Filament\Resources\Domains\Actions\DomainDnsCheckAction;
use App\Filament\Resources\Domains\Actions\DomainVerifyAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class DomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('status', 'desc')
            ->columns([
                TextColumn::make('domain')
                    ->label('Domínio')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->searchable(),
                TextColumn::make('panel')
                    ->label('Painel')
                    ->formatStateUsing(fn (?DomainPanel $state): ?string => $state?->label()),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (?DomainStatus $state): ?string => $state?->label())
                    ->color(fn (?DomainStatus $state): string => $state === DomainStatus::Active ? 'success' : 'warning'),
                TextColumn::make('verified_at')
                    ->label('Verificado em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Situação')
                    ->options([
                        DomainStatus::Pending->value => DomainStatus::Pending->label(),
                        DomainStatus::Active->value => DomainStatus::Active->label(),
                    ]),
            ])
            ->recordActions([
                DomainDnsCheckAction::make(),
                DomainVerifyAction::make(),
            ]);
    }
}
