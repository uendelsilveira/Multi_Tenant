<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Contracts\TenantEnvironmentInterface;
use App\Exceptions\Tenant\TenantProvisioningException;
use App\Models\Tenant;
use Closure;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Events\DatabaseCreated;

final class StanclTenantEnvironment implements TenantEnvironmentInterface
{
    public function ensureDatabaseExists(Tenant $tenant): void
    {
        $tenant->database()->makeCredentials();

        $name = $tenant->database()->getName();

        if ($name === null || $name === '') {
            throw TenantProvisioningException::databaseNameUnavailable($tenant->id);
        }

        $manager = $tenant->database()->manager();

        if ($manager->databaseExists($name)) {
            return;
        }

        $manager->createDatabase($tenant);

        event(new DatabaseCreated($tenant));
    }

    public function migrate(Tenant $tenant): void
    {
        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->id],
            '--force' => true,
        ]);
    }

    public function run(Tenant $tenant, Closure $callback): mixed
    {
        return $tenant->run($callback);
    }
}
