<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\Actions\Tenant\ChangeProvisionalPasswordAction;
use App\Exceptions\DomainException;
use App\Models\TenantUser;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\Rules\Password;

/**
 * Primeiro acesso: troca obrigatória da senha provisória (RF10).
 *
 * @property-read Schema $form
 */
final class ChangeProvisionalPassword extends Page
{
    protected static ?string $slug = 'trocar-senha-provisoria';

    protected static ?string $title = 'Defina sua senha';

    protected static bool $shouldRegisterNavigation = false;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        /** @var Authenticatable|null $user */
        $user = Filament::auth()->user();

        if (! $user instanceof TenantUser || ! $user->must_change_password) {
            $this->redirect(Filament::getUrl() ?? '/admin');

            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('password')
                    ->label('Nova senha')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(Password::min(8)->letters()->numbers())
                    ->same('password_confirmation')
                    ->helperText('Pelo menos 8 caracteres, com letras e números.'),
                TextInput::make('password_confirmation')
                    ->label('Confirme a nova senha')
                    ->password()
                    ->revealable()
                    ->required()
                    ->dehydrated(false),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Salvar e continuar')
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        /** @var Authenticatable|null $user */
        $user = Filament::auth()->user();

        if (! $user instanceof TenantUser) {
            return;
        }

        /** @var array{password: string} $data */
        $data = $this->form->getState();

        try {
            app(ChangeProvisionalPasswordAction::class)->execute($user, $data['password']);
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        }

        // A senha mudou: mantém a sessão atual válida para o guard do painel.
        if (request()->hasSession()) {
            request()->session()->put([
                'password_hash_'.Filament::getAuthGuard() => $user->refresh()->getAuthPassword(),
            ]);
        }

        Notification::make()->title('Senha definida. Bem-vindo!')->success()->send();

        $this->redirect(Filament::getUrl() ?? '/admin');
    }
}
