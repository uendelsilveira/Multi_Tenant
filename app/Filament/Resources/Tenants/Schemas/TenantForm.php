<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Schemas;

use App\Enums\BillingCycle;
use App\Enums\DomainPanel;
use App\Enums\PaymentGateway;
use App\Enums\PersonType;
use App\Models\Tenant;
use App\Services\PlanService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificação')
                ->columns(2)
                ->schema([
                    TextInput::make('id')
                        ->label('Slug')
                        ->helperText('Identificador permanente do tenant. Define o nome do banco e não pode ser alterado depois.')
                        ->required()
                        ->regex('/^[a-z][a-z0-9-]{2,49}$/')
                        ->validationMessages(['regex' => 'Use de 3 a 50 caracteres: letras minúsculas, números e hífen, começando por letra.'])
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit'),
                    Select::make('person_type')
                        ->label('Tipo')
                        ->options(PersonType::options())
                        ->default(PersonType::Company->value)
                        ->required(),
                    TextInput::make('legal_name')
                        ->label('Razão social / nome completo')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('trade_name')
                        ->label('Nome fantasia')
                        ->maxLength(255),
                    TextInput::make('document')
                        ->label('CNPJ ou CPF')
                        ->required()
                        ->maxLength(18)
                        ->dehydrateStateUsing(fn (?string $state): string => self::onlyAlphanumeric($state)),
                    TextInput::make('state_registration')
                        ->label('Inscrição estadual')
                        ->maxLength(30),
                ]),
            Section::make('Contato')
                ->columns(3)
                ->schema([
                    TextInput::make('contact_name')
                        ->label('Responsável')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('contact_email')
                        ->label('E-mail')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('contact_phone')
                        ->label('Telefone')
                        ->tel()
                        ->required()
                        ->maxLength(20)
                        ->dehydrateStateUsing(fn (?string $state): string => self::onlyDigits($state)),
                ]),
            Section::make('Endereço')
                ->columns(6)
                ->schema([
                    TextInput::make('zip_code')
                        ->label('CEP')
                        ->required()
                        ->maxLength(9)
                        ->dehydrateStateUsing(fn (?string $state): string => self::onlyDigits($state))
                        ->columnSpan(2),
                    TextInput::make('street')
                        ->label('Logradouro')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(3),
                    TextInput::make('number')
                        ->label('Número')
                        ->required()
                        ->maxLength(20),
                    TextInput::make('complement')
                        ->label('Complemento')
                        ->maxLength(255)
                        ->columnSpan(2),
                    TextInput::make('district')
                        ->label('Bairro')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    TextInput::make('city')
                        ->label('Cidade')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('state')
                        ->label('UF')
                        ->required()
                        ->length(2)
                        ->dehydrateStateUsing(fn (?string $state): string => mb_strtoupper(trim((string) $state))),
                ]),
            Section::make('Comercial')
                ->columns(3)
                ->schema([
                    Select::make('plan_id')
                        ->label('Plano')
                        ->options(fn (?Tenant $record): array => app(PlanService::class)->selectableOptions($record?->plan_id))
                        ->required(),
                    Select::make('billing_cycle')
                        ->label('Ciclo de cobrança')
                        ->options(BillingCycle::options())
                        ->required(),
                    Select::make('billing_gateway')
                        ->label('Gateway de cobrança')
                        ->options(PaymentGateway::options())
                        ->default(PaymentGateway::Asaas->value)
                        ->required()
                        ->disabledOn('edit')
                        ->helperText('A assinatura é criada no gateway logo após o cadastro. Não pode ser trocado depois.'),
                ]),
            Section::make('Domínios')
                ->description('Pelo menos um domínio, e ao menos um apontando para o painel Admin. Informe o endereço completo, sem http://.')
                ->schema([
                    Repeater::make('domains')
                        ->hiddenLabel()
                        ->columns(2)
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('Adicionar domínio')
                        ->schema([
                            TextInput::make('domain')
                                ->label('Domínio')
                                ->placeholder('cliente.exemplo.com.br')
                                ->required()
                                ->maxLength(253)
                                ->dehydrateStateUsing(fn (?string $state): string => mb_strtolower(trim((string) $state))),
                            Select::make('panel')
                                ->label('Painel')
                                ->options(DomainPanel::options())
                                ->default(DomainPanel::Admin->value)
                                ->required(),
                        ]),
                ]),
            Section::make('Observações internas')
                ->description('Visível apenas no painel central.')
                ->schema([
                    Textarea::make('notes')
                        ->hiddenLabel()
                        ->rows(3),
                ]),
        ]);
    }

    private static function onlyDigits(?string $state): string
    {
        return (string) preg_replace('/\D/', '', (string) $state);
    }

    private static function onlyAlphanumeric(?string $state): string
    {
        return mb_strtoupper((string) preg_replace('/[^0-9A-Za-z]/', '', (string) $state));
    }
}
