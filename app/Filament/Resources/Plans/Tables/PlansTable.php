<?php

declare(strict_types=1);

namespace App\Filament\Resources\Plans\Tables;

use App\Filament\Resources\Plans\Actions\PlanDeleteAction;
use App\Models\Plan;
use App\Models\PlanPrice;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('prices')->withCount('tenants'))
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('prices_summary')
                    ->label('Preços')
                    ->state(fn (Plan $record): string => $record->prices
                        ->map(fn (PlanPrice $price): string => $price->billing_cycle->label().': R$ '.number_format((float) $price->price, 2, ',', '.'))
                        ->implode(' · ')),
                TextColumn::make('tenants_count')
                    ->label('Tenants'),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                PlanDeleteAction::make(),
            ]);
    }
}
