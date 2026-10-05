<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Tables;

use App\Enums\BillingCycle;
use App\Enums\ProvisioningStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Filament\Resources\Tenants\Actions\TenantChangeStatusAction;
use App\Filament\Resources\Tenants\Actions\TenantResendPasswordAction;
use App\Filament\Resources\Tenants\Actions\TenantRestoreAction;
use App\Filament\Resources\Tenants\Actions\TenantRetryProvisioningAction;
use App\Filament\Resources\Tenants\Actions\TenantRetrySubscriptionAction;
use App\Filament\Resources\Tenants\Actions\TenantSoftDeleteAction;
use App\Models\Tenant;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

final class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
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
                TextColumn::make('provisioning_status')
                    ->label('Ambiente')
                    ->badge()
                    ->formatStateUsing(fn (?ProvisioningStatus $state): ?string => $state?->label())
                    ->color(fn (?ProvisioningStatus $state): string => $state?->color() ?? 'gray')
                    ->tooltip(fn (Tenant $record): ?string => $record->provisioning_error),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (?TenantStatus $state): ?string => $state?->label())
                    ->color(fn (?TenantStatus $state): string => $state === TenantStatus::Active ? 'success' : 'warning')
                    ->description(fn (Tenant $record): ?string => $record->status_locked_until?->isFuture()
                        ? 'Travado até '.$record->status_locked_until->format('d/m/Y H:i')
                        : null),
                TextColumn::make('subscription.status')
                    ->label('Cobrança')
                    ->badge()
                    ->formatStateUsing(fn (?SubscriptionStatus $state): ?string => $state?->label())
                    ->color(fn (?SubscriptionStatus $state): string => $state?->color() ?? 'gray')
                    ->description(fn (Tenant $record): ?string => $record->subscription?->gateway->label())
                    ->tooltip(fn (Tenant $record): ?string => $record->subscription?->last_error)
                    ->placeholder('—'),
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
                ActionGroup::make([
                    TenantChangeStatusAction::make(),
                    TenantRetrySubscriptionAction::make(),
                    TenantRetryProvisioningAction::make(),
                    TenantResendPasswordAction::make(),
                    TenantSoftDeleteAction::make(),
                    TenantRestoreAction::make(),
                ]),
            ]);
    }
}
