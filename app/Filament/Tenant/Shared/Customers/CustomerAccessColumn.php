<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Shared\Customers;

use App\Models\Customer;
use Filament\Tables\Columns\TextColumn;

final class CustomerAccessColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('access')
            ->label('Acesso')
            ->badge()
            ->state(fn (Customer $record): string => match (true) {
                ! $record->is_active => 'Desativado',
                $record->must_change_password => 'Aguardando primeiro acesso',
                default => 'Ativo',
            })
            ->color(fn (string $state): string => match ($state) {
                'Ativo' => 'success',
                'Desativado' => 'danger',
                default => 'warning',
            });
    }
}
