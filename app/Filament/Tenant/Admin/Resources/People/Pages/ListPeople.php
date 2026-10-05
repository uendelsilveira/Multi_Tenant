<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\People\Pages;

use App\Filament\Tenant\Admin\Resources\People\PersonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListPeople extends ListRecords
{
    protected static string $resource = PersonResource::class;

    /** @return array<int, CreateAction> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
