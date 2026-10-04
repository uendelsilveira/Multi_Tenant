<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('id')
                ->label('ID / Subdomínio')
                ->required()
                ->disabled(fn (string $operation): bool => $operation !== 'create'),
            TextInput::make('tenant_name')
                ->label('Nome do Tenant')
                ->required(),
        ]);
    }
}
