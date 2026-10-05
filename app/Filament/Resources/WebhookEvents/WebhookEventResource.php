<?php

declare(strict_types=1);

namespace App\Filament\Resources\WebhookEvents;

use App\Filament\Resources\WebhookEvents\Pages\ListWebhookEvents;
use App\Filament\Resources\WebhookEvents\Tables\WebhookEventsTable;
use App\Models\WebhookEvent;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class WebhookEventResource extends Resource
{
    protected static ?string $model = WebhookEvent::class;

    protected static ?string $slug = 'eventos-de-cobranca';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Eventos de cobrança';

    protected static ?string $modelLabel = 'evento de cobrança';

    protected static ?string $pluralModelLabel = 'eventos de cobrança';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return WebhookEventsTable::configure($table);
    }

    /** @return array<string, PageRegistration> */
    public static function getPages(): array
    {
        return [
            'index' => ListWebhookEvents::route('/'),
        ];
    }
}
