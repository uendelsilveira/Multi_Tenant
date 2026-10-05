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
use App\Http\Middleware\EnsureTenantIsNotSuspended;
use App\Http\Middleware\EnsureTenantIsProvisioned;
use App\Http\Middleware\InitializeTenancyForTenantDomain;
use App\Models\Domain;
use App\Models\Tenant;
use App\Observers\DomainObserver;
use App\Observers\TenantObserver;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\DomainRepositoryInterface;
use App\Repositories\Contracts\FeatureRepositoryInterface;
use App\Repositories\Contracts\FeatureSettingRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use App\Repositories\Contracts\WebhookEventRepositoryInterface;
use App\Repositories\Eloquent\CustomerRepository;
use App\Repositories\Eloquent\DomainRepository;
use App\Repositories\Eloquent\FeatureRepository;
use App\Repositories\Eloquent\FeatureSettingRepository;
use App\Repositories\Eloquent\PlanRepository;
use App\Repositories\Eloquent\RoleRepository;
use App\Repositories\Eloquent\SubscriptionRepository;
use App\Repositories\Eloquent\TenantRepository;
use App\Repositories\Eloquent\TenantUserRepository;
use App\Repositories\Eloquent\WebhookEventRepository;
use App\Services\Billing\AsaasGateway;
use App\Services\Billing\PaymentGatewayRegistry;
use App\Services\Billing\StripeGateway;
use App\Services\BillingService;
use App\Services\PermissionCatalog;
use App\Services\Tenancy\MailProvisionalPasswordNotifier;
use App\Services\Tenancy\StanclTenantEnvironment;
use App\Services\Tenancy\SystemDnsLookup;
use App\Services\TenantFeatureService;
use App\Services\TenantService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\RequireLivewireHeaders;

final class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        CustomerRepositoryInterface::class => CustomerRepository::class,
        DomainRepositoryInterface::class => DomainRepository::class,
        FeatureRepositoryInterface::class => FeatureRepository::class,
        FeatureSettingRepositoryInterface::class => FeatureSettingRepository::class,
        PlanRepositoryInterface::class => PlanRepository::class,
        RoleRepositoryInterface::class => RoleRepository::class,
        SubscriptionRepositoryInterface::class => SubscriptionRepository::class,
        WebhookEventRepositoryInterface::class => WebhookEventRepository::class,
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

        $this->app->bind(PaymentGatewayRegistry::class, fn (Application $app): PaymentGatewayRegistry => new PaymentGatewayRegistry(
            new AsaasGateway($app->make(HttpClient::class), (array) config('billing.asaas', []), (int) config('billing.first_due_in_days', 7)),
            new StripeGateway($app->make(HttpClient::class), (array) config('billing.stripe', []), (int) config('billing.first_due_in_days', 7)),
        ));

        $this->app->when(BillingService::class)
            ->needs('$graceDays')
            ->giveConfig('billing.grace_days', 10);

        // Uma instância por requisição ou job: reaproveita a leitura do plano sem guardar entre requisições.
        $this->app->scoped(TenantFeatureService::class);

        $this->app->when(PermissionCatalog::class)
            ->needs('$catalog')
            ->giveConfig('permissions.catalog', []);
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
            EnsureTenantIsNotSuspended::class,
        ]));
    }
}
