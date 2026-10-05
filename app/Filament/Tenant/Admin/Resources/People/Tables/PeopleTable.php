<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\People\Tables;

use App\Filament\Tenant\Admin\Resources\People\Actions\PersonActivateAction;
use App\Filament\Tenant\Admin\Resources\People\Actions\PersonDeactivateAction;
use App\Filament\Tenant\Admin\Resources\People\Actions\PersonResendPasswordAction;
use App\Models\TenantUser;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PeopleTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('role'))
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('role.name')
                    ->label('Perfil')
                    ->placeholder('—'),
                TextColumn::make('panel')
                    ->label('Painel')
                    ->state(fn (TenantUser $record): ?string => $record->type?->label())
                    ->placeholder('—'),
                TextColumn::make('access')
                    ->label('Acesso')
                    ->badge()
                    ->state(fn (TenantUser $record): string => match (true) {
                        ! $record->is_active => 'Desativada',
                        $record->must_change_password => 'Aguardando primeiro acesso',
                        default => 'Ativa',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Ativa' => 'success',
                        'Desativada' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    PersonResendPasswordAction::make(),
                    PersonDeactivateAction::make(),
                    PersonActivateAction::make(),
                ]),
            ]);
    }
}
