<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Customers\Pages;

use App\Filament\Tenant\Admin\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\ListRecords;

final class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;
}
