<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\People;

use App\Filament\Tenant\Admin\Resources\People\Pages\CreatePerson;
use App\Filament\Tenant\Admin\Resources\People\Pages\EditPerson;
use App\Filament\Tenant\Admin\Resources\People\Pages\ListPeople;
use App\Filament\Tenant\Admin\Resources\People\Schemas\PersonForm;
use App\Filament\Tenant\Admin\Resources\People\Tables\PeopleTable;
use App\Models\TenantUser;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class PersonResource extends Resource
{
    protected static ?string $model = TenantUser::class;

    protected static ?string $slug = 'pessoas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'pessoa';

    protected static ?string $pluralModelLabel = 'pessoas';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PersonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PeopleTable::configure($table);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListPeople::route('/'),
            'create' => CreatePerson::route('/create'),
            'edit' => EditPerson::route('/{record}/edit'),
        ];
    }
}
