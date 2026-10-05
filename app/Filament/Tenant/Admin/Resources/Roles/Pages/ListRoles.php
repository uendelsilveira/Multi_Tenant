<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Roles\Pages;

use App\Filament\Tenant\Admin\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    /** @return array<int, CreateAction> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
