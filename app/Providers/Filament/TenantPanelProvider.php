<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\DomainPanel;
use App\Filament\Tenant\Pages\ChangeProvisionalPassword;
use App\Http\Middleware\EnforceProvisionalPasswordChange;
use App\Http\Middleware\EnsureDomainMatchesPanel;
use App\Http\Middleware\EnsureTenantIsProvisioned;
use App\Http\Middleware\InitializeTenancyForTenantDomain;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Base dos três painéis de tenant (ADR-0002, ADR-0008). Cada painel tem seu
 * caminho e só responde no domínio que aponta para ele. Os três usam o mesmo
 * guard; quem decide a entrada é o tipo base da pessoa.
 */
abstract class TenantPanelProvider extends PanelProvider
{
    abstract protected function domainPanel(): DomainPanel;

    /** Pasta em app/Filament/Tenant onde ficam resources, páginas e widgets do painel. */
    abstract protected function directory(): string;

    /** @return array<int|string, string> */
    abstract protected function primaryColor(): array;

    public function panel(Panel $panel): Panel
    {
        $domainPanel = $this->domainPanel();
        $directory = $this->directory();
        $namespace = "App\\Filament\\Tenant\\{$directory}";

        return $panel
            ->id($domainPanel->panelId())
            ->path($domainPanel->path())
            ->login()
            ->passwordReset()
            ->authGuard('tenant')
            ->authPasswordBroker('tenant_users')
            ->colors([
                'primary' => $this->primaryColor(),
            ])
            ->discoverResources(in: app_path("Filament/Tenant/{$directory}/Resources"), for: "{$namespace}\\Resources")
            ->discoverPages(in: app_path("Filament/Tenant/{$directory}/Pages"), for: "{$namespace}\\Pages")
            ->discoverWidgets(in: app_path("Filament/Tenant/{$directory}/Widgets"), for: "{$namespace}\\Widgets")
            ->pages([
                Dashboard::class,
                ChangeProvisionalPassword::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                InitializeTenancyForTenantDomain::class,
                PreventAccessFromCentralDomains::class,
                EnsureTenantIsProvisioned::class,
                EnsureDomainMatchesPanel::class.':'.$domainPanel->value,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnforceProvisionalPasswordChange::class,
            ]);
    }
}
