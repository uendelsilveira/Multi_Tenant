<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Tables;

use App\Enums\BillingCycle;
use App\Enums\TenantStatus;
use App\Filament\Resources\Tenants\Actions\TenantRestoreAction;
use App\Filament\Resources\Tenants\Actions\TenantSoftDeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('legal_name')
                    ->label('Razão social')
                    ->searchable(),
                TextColumn::make('plan.name')
                    ->label('Plano')
                    ->placeholder('—'),
                TextColumn::make('billing_cycle')
                    ->label('Ciclo')
                    ->formatStateUsing(fn (?BillingCycle $state): ?string => $state?->label())
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (?TenantStatus $state): ?string => $state?->label())
                    ->color(fn (?TenantStatus $state): string => $state === TenantStatus::Active ? 'success' : 'warning'),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                TenantSoftDeleteAction::make(),
                TenantRestoreAction::make(),
            ]);
    }
}
