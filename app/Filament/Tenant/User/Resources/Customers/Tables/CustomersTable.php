<?php

declare(strict_types=1);

namespace App\Filament\Tenant\User\Resources\Customers\Tables;

use App\Filament\Tenant\Shared\Customers\CustomerAccessColumn;
use App\Filament\Tenant\Shared\Customers\CustomerResendPasswordAction;
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
                TextColumn::make('profile.phone')
                    ->label('Telefone')
                    ->placeholder('—'),
                CustomerAccessColumn::make(),
            ])
            ->recordActions([
                EditAction::make(),
                CustomerResendPasswordAction::make(),
            ]);
    }
}
