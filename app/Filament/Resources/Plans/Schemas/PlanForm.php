<?php

declare(strict_types=1);

namespace App\Filament\Resources\Plans\Schemas;

use App\Enums\BillingCycle;
use App\Services\FeatureService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Plano')
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label('Descrição')
                        ->rows(3),
                    Toggle::make('is_active')
                        ->label('Ativo')
                        ->helperText('Plano inativo não pode ser contratado por novos tenants.')
                        ->default(true),
                ]),
            Section::make('Preços por ciclo')
                ->description('Informe o preço dos ciclos que o plano oferece. Ciclo sem preço não é oferecido.')
                ->columns(3)
                ->schema(array_map(
                    fn (BillingCycle $cycle): TextInput => TextInput::make("prices.{$cycle->value}")
                        ->label($cycle->label())
                        ->numeric()
                        ->minValue(0)
                        ->prefix('R$'),
                    BillingCycle::cases(),
                )),
            Section::make('Funcionalidades')
                ->description('Catálogo declarado em config/features.php e sincronizado com features:sync.')
                ->schema([
                    CheckboxList::make('feature_ids')
                        ->hiddenLabel()
                        ->options(fn (): array => app(FeatureService::class)->options())
                        ->columns(2),
                ]),
        ]);
    }
}
