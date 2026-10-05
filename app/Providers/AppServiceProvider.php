<?php

declare(strict_types=1);

/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

namespace App\Providers;

use App\Contracts\DnsLookupInterface;
use App\Contracts\ProvisionalPasswordNotifierInterface;
use App\Contracts\TenantEnvironmentInterface;
use App\Http\Middleware\EnsureTenantIsProvisioned;
use App\Http\Middleware\InitializeTenancyForTenantDomain;
use App\Models\Domain;
use App\Models\Tenant;
use App\Observers\DomainObserver;
use App\Observers\TenantObserver;
use App\Repositories\Contracts\DomainRepositoryInterface;
use App\Repositories\Contracts\FeatureRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use App\Repositories\Eloquent\DomainRepository;
use App\Repositories\Eloquent\FeatureRepository;
use App\Repositories\Eloquent\PlanRepository;
use App\Repositories\Eloquent\TenantRepository;
use App\Repositories\Eloquent\TenantUserRepository;
use App\Services\Tenancy\MailProvisionalPasswordNotifier;
use App\Services\Tenancy\StanclTenantEnvironment;
use App\Services\Tenancy\SystemDnsLookup;
use App\Services\TenantService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\RequireLivewireHeaders;

final class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        DomainRepositoryInterface::class => DomainRepository::class,
        FeatureRepositoryInterface::class => FeatureRepository::class,
        PlanRepositoryInterface::class => PlanRepository::class,
        TenantRepositoryInterface::class => TenantRepository::class,
        TenantUserRepositoryInterface::class => TenantUserRepository::class,
        TenantEnvironmentInterface::class => StanclTenantEnvironment::class,
        ProvisionalPasswordNotifierInterface::class => MailProvisionalPasswordNotifier::class,
        DnsLookupInterface::class => SystemDnsLookup::class,
    ];

    public function register(): void
    {
        $this->app->when(TenantService::class)
            ->needs('$centralDomains')
            ->giveConfig('tenancy.central_domains', []);
    }

    public function boot(): void
    {
        Domain::observe(DomainObserver::class);
        Tenant::observe(TenantObserver::class);

        // Toda interação de tela passa por esta rota. Em domínio de tenant ela
        // precisa inicializar a tenancy antes da sessão, que mora no banco do tenant.
        Livewire::setUpdateRoute(fn (mixed $handle, string $path) => Route::post($path, $handle)->middleware([
            'web',
            RequireLivewireHeaders::class,
            InitializeTenancyForTenantDomain::class,
            EnsureTenantIsProvisioned::class,
        ]));
    }
}
