<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Customers\Tables;

use App\Filament\Tenant\Admin\Resources\Customers\Actions\CustomerActiveAction;
use App\Filament\Tenant\Admin\Resources\Customers\Actions\CustomerResponsiblesAction;
use App\Filament\Tenant\Shared\Customers\CustomerAccessColumn;
use App\Filament\Tenant\Shared\Customers\CustomerResendPasswordAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('responsibles.name')
                    ->label('Responsáveis')
                    ->badge()
                    ->placeholder('—'),
                CustomerAccessColumn::make(),
            ])
            ->recordActions([
                CustomerResponsiblesAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    CustomerResendPasswordAction::make(),
                    CustomerActiveAction::deactivate(),
                    CustomerActiveAction::activate(),
                ]),
            ]);
    }
}
