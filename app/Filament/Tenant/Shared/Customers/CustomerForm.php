<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Shared\Customers;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class CustomerForm
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
                ->helperText('O acesso ao portal é enviado para este e-mail.'),
            TextInput::make('phone')
                ->label('Telefone')
                ->tel()
                ->maxLength(20)
                ->dehydrateStateUsing(fn (?string $state): ?string => self::clean($state, '/\D/')),
            TextInput::make('document')
                ->label('CPF ou CNPJ')
                ->maxLength(18)
                ->dehydrateStateUsing(fn (?string $state): ?string => self::clean($state, '/[^0-9A-Za-z]/')),
            Textarea::make('notes')
                ->label('Observações')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    private static function clean(?string $state, string $pattern): ?string
    {
        $clean = mb_strtoupper((string) preg_replace($pattern, '', (string) $state));

        return $clean === '' ? null : $clean;
    }
}
