<?php

/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\EnsureTenantIsProvisioned;
use App\Http\Middleware\InitializeTenancyForTenantDomain;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

final class TenancyServiceProvider extends ServiceProvider
{
    // By default, no namespace is used to support the callable array syntax.
    public static string $controllerNamespace = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function events(): array
    {
        return [
            // Tenant events
            Events\CreatingTenant::class => [],
            // O banco do tenant não é mais criado aqui. O cadastro emite TenantRegistered
            // e o provisionamento roda em fila (App\Jobs\ProvisionTenantJob, RF11).
            Events\TenantCreated::class => [],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            // A exclusão de tenant é lógica (soft delete): o banco é mantido (RN24).
            // Não registrar Jobs\DeleteDatabase aqui.
            Events\TenantDeleted::class => [],

            // Domain events
            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],

            // Database events
            Events\DatabaseCreated::class => [],
            Events\DatabaseMigrated::class => [],
            Events\DatabaseSeeded::class => [],
            Events\DatabaseRolledBack::class => [],
            Events\DatabaseDeleted::class => [],

            // Tenancy events
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\EndingTenancy::class => [],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],

            Events\BootstrappingTenancy::class => [],
            Events\TenancyBootstrapped::class => [],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],

            // Resource syncing
            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],

            // Fired only when a synced resource is changed in a different DB than the origin DB (to avoid infinite loops)
            Events\SyncedResourceChangedInForeignDatabase::class => [],
        ];
    }

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootEvents();
        $this->mapRoutes();

        $this->makeTenancyMiddlewareHighestPriority();
    }

    protected function bootEvents(): void
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }
    }

    protected function mapRoutes(): void
    {
        $this->app->booted(function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->group(base_path('routes/tenant.php'));
            }
        });
    }

    protected function makeTenancyMiddlewareHighestPriority(): void
    {
        if ($this->app->has(Kernel::class)) {
            /** @var Kernel $kernel */
            $kernel = $this->app->make(Kernel::class);

            $tenancyMiddleware = [
                // Even higher priority than the initialization middleware
                Middleware\PreventAccessFromCentralDomains::class,

                Middleware\InitializeTenancyByDomain::class,
                Middleware\InitializeTenancyBySubdomain::class,
                Middleware\InitializeTenancyByDomainOrSubdomain::class,
                Middleware\InitializeTenancyByPath::class,
                Middleware\InitializeTenancyByRequestData::class,

                // Resolução própria por domínio verificado, e a espera pelo provisionamento:
                // logo depois de identificar o tenant e antes da sessão, que já usa o banco dele.
                InitializeTenancyForTenantDomain::class,
                EnsureTenantIsProvisioned::class,
            ];

            foreach (array_reverse($tenancyMiddleware) as $middleware) {
                $kernel->prependToMiddlewarePriority($middleware);
            }
        }
    }
}
