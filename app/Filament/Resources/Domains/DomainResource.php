<?php

declare(strict_types=1);

namespace App\Filament\Resources\Domains;

use App\Filament\Resources\Domains\Pages\ListDomains;
use App\Filament\Resources\Domains\Tables\DomainsTable;
use App\Models\Domain;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $modelLabel = 'domínio';

    protected static ?string $pluralModelLabel = 'domínios';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return DomainsTable::configure($table);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListDomains::route('/'),
        ];
    }
}
