<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Roles\Schemas;

use App\Enums\TenantUserType;
use App\Services\RoleService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

final class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Select::make('base_type')
                ->label('Tipo')
                ->options(TenantUserType::options())
                ->required()
                ->live()
                // As permissões dependem do tipo: ao trocar, a seleção recomeça.
                ->afterStateUpdated(fn (Set $set): mixed => $set('permissions', []))
                ->helperText('Define em qual painel as pessoas deste perfil entram. Ao mudar o tipo, todas elas mudam de painel.'),
            CheckboxList::make('permissions')
                ->label('Permissões')
                ->options(function (Get $get): array {
                    $type = TenantUserType::tryFrom((string) $get('base_type'));

                    return $type === null ? [] : app(RoleService::class)->permissionOptions($type);
                })
                ->helperText('Só aparecem as permissões que existem para o tipo escolhido.')
                ->columnSpanFull(),
        ]);
    }
}
