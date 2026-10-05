<?php

declare(strict_types=1);

namespace App\Filament\Resources\Domains\Pages;

use App\Filament\Resources\Domains\DomainResource;
use Filament\Resources\Pages\ListRecords;

final class ListDomains extends ListRecords
{
    protected static string $resource = DomainResource::class;
}
