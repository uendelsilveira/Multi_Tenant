<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Roles\Tables;

use App\Filament\Tenant\Admin\Resources\Roles\Actions\RoleDeleteAction;
use App\Models\Role;
use App\Services\RoleService;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('users'))
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('panel')
                    ->label('Tipo')
                    ->state(fn (Role $record): ?string => $record->base_type?->label()),
                TextColumn::make('origin')
                    ->label('Origem')
                    ->badge()
                    ->state(fn (Role $record): string => $record->is_system ? 'Sistema' : 'Customizado')
                    ->color(fn (string $state): string => $state === 'Sistema' ? 'gray' : 'info'),
                TextColumn::make('permission_count')
                    ->label('Permissões')
                    ->state(fn (Role $record): int => count(app(RoleService::class)->permissionsOf($record))),
                TextColumn::make('users_count')
                    ->label('Pessoas'),
            ])
            ->recordActions([
                EditAction::make(),
                RoleDeleteAction::make(),
            ]);
    }
}
