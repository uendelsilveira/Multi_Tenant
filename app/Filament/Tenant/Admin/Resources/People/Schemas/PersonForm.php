<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\People\Schemas;

use App\Services\RoleService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class PersonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('A senha provisória do primeiro acesso é enviada para este e-mail.'),
            Select::make('role_id')
                ->label('Perfil')
                ->options(fn (): array => app(RoleService::class)->assignableOptions())
                ->required()
                ->helperText('O tipo do perfil define em qual painel a pessoa entra.'),
        ]);
    }
}
