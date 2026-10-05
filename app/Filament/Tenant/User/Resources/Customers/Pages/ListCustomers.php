<?php

declare(strict_types=1);

namespace App\Filament\Tenant\User\Resources\Customers\Pages;

use App\Filament\Tenant\User\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    /** @return array<int, CreateAction> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
