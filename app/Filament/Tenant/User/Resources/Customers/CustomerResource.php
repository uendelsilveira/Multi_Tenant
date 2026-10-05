<?php

declare(strict_types=1);

namespace App\Filament\Tenant\User\Resources\Customers;

use App\Filament\Tenant\Shared\Customers\CustomerForm;
use App\Filament\Tenant\User\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Tenant\User\Resources\Customers\Pages\EditCustomer;
use App\Filament\Tenant\User\Resources\Customers\Pages\ListCustomers;
use App\Filament\Tenant\User\Resources\Customers\Tables\CustomersTable;
use App\Models\Customer;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $slug = 'clientes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    /**
     * Filtro de interface: só os clientes vinculados a quem está logado.
     * A regra em si está no CustomerService e na Policy.
     *
     * @return Builder<Model>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('profile')
            ->whereHas('responsibles', fn (Builder $query): Builder => $query->whereKey(Filament::auth()->id()));
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
