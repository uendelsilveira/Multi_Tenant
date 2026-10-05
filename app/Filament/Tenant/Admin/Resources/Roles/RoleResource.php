<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Roles;

use App\Filament\Tenant\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Tenant\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Tenant\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Tenant\Admin\Resources\Roles\Schemas\RoleForm;
use App\Filament\Tenant\Admin\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $slug = 'perfis';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $modelLabel = 'perfil';

    protected static ?string $pluralModelLabel = 'perfis';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
