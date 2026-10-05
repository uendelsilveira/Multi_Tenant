<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Pages;

use App\Actions\Feature\ToggleFeatureAction;
use App\Exceptions\DomainException;
use App\Models\Feature;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Services\PermissionCatalog;
use App\Services\TenantAccessService;
use App\Services\TenantFeatureService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Liga e desliga as funcionalidades que o plano do tenant inclui (RF14).
 * O que o plano não inclui não aparece.
 *
 * @property-read Schema $form
 */
final class ManageFeatures extends Page
{
    protected static ?string $slug = 'funcionalidades';

    protected static ?string $title = 'Funcionalidades';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static ?int $navigationSort = 3;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        /** @var Authenticatable|null $user */
        $user = Filament::auth()->user();

        return $user instanceof TenantUser
            && app(TenantAccessService::class)->allows($user, PermissionCatalog::FEATURES_MANAGE);
    }

    public function mount(): void
    {
        $tenant = tenant();

        $this->form->fill([
            'enabled' => $tenant instanceof Tenant ? app(TenantFeatureService::class)->activeKeys($tenant) : [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $options = $this->options();

        return $schema
            ->statePath('data')
            ->components([
                CheckboxList::make('enabled')
                    ->label('Funcionalidades do seu plano')
                    ->options($options)
                    ->columns(2)
                    ->bulkToggleable()
                    ->helperText($options === []
                        ? 'O plano contratado não inclui funcionalidades opcionais.'
                        : 'Marque o que a empresa vai usar. Desmarcar tira a funcionalidade do menu e bloqueia as telas dela, sem apagar nenhum dado.'),
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
                            ->label('Salvar')
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        /** @var array{enabled?: list<string>} $data */
        $data = $this->form->getState();
        $wanted = $data['enabled'] ?? [];
        $current = app(TenantFeatureService::class)->activeKeys($tenant);

        try {
            foreach (array_diff($wanted, $current) as $key) {
                app(ToggleFeatureAction::class)->execute($tenant, $key, true);
            }

            foreach (array_diff($current, $wanted) as $key) {
                app(ToggleFeatureAction::class)->execute($tenant, $key, false);
            }
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title('Funcionalidades atualizadas.')->success()->send();
    }

    /**
     * Só transformação de formato: chave => "Módulo · Nome".
     *
     * @return array<string, string>
     */
    private function options(): array
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return [];
        }

        $options = [];

        foreach (app(TenantFeatureService::class)->inPlan($tenant) as $feature) {
            /** @var Feature $feature */
            $options[$feature->key] = $feature->module === null ? $feature->name : "{$feature->module} · {$feature->name}";
        }

        return $options;
    }
}
