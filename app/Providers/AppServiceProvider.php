<?php

declare(strict_types=1);

/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

namespace App\Providers;

use App\Contracts\ProvisionalPasswordNotifierInterface;
use App\Contracts\TenantEnvironmentInterface;
use App\Repositories\Contracts\FeatureRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use App\Repositories\Eloquent\FeatureRepository;
use App\Repositories\Eloquent\PlanRepository;
use App\Repositories\Eloquent\TenantRepository;
use App\Repositories\Eloquent\TenantUserRepository;
use App\Services\Tenancy\MailProvisionalPasswordNotifier;
use App\Services\Tenancy\StanclTenantEnvironment;
use App\Services\TenantService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        FeatureRepositoryInterface::class => FeatureRepository::class,
        PlanRepositoryInterface::class => PlanRepository::class,
        TenantRepositoryInterface::class => TenantRepository::class,
        TenantUserRepositoryInterface::class => TenantUserRepository::class,
        TenantEnvironmentInterface::class => StanclTenantEnvironment::class,
        ProvisionalPasswordNotifierInterface::class => MailProvisionalPasswordNotifier::class,
    ];

    public function register(): void
    {
        $this->app->when(TenantService::class)
            ->needs('$centralDomains')
            ->giveConfig('tenancy.central_domains', []);
    }

    public function boot(): void
    {
        //
    }
}
