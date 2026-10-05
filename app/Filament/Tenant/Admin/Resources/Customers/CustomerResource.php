<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Customers;

use App\Filament\Tenant\Admin\Resources\Customers\Pages\EditCustomer;
use App\Filament\Tenant\Admin\Resources\Customers\Pages\ListCustomers;
use App\Filament\Tenant\Admin\Resources\Customers\Tables\CustomersTable;
use App\Filament\Tenant\Shared\Customers\CustomerForm;
use App\Models\Customer;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * No painel admin o cliente não é cadastrado: aqui se veem todos, se ajustam
 * os vínculos e se desativa.
 */
final class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $slug = 'clientes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    /** @return Builder<Model> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['profile', 'responsibles']);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
