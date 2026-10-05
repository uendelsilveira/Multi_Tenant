<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use App\Models\Tenant;
use App\Services\TenantStatusService;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Job de módulo não executa com o tenant suspenso: termina sem fazer nada e
 * sem contar como falha. Cobre também o que já estava na fila antes da suspensão.
 *
 * Uso, dentro do job: public function middleware(): array { return [new SkipWhenTenantIsSuspended]; }
 */
final class SkipWhenTenantIsSuspended
{
    public function handle(object $job, Closure $next): void
    {
        $tenant = tenant();

        if ($tenant instanceof Tenant && app(TenantStatusService::class)->isSuspended($tenant->refresh())) {
            Log::info('job.skipped_tenant_suspended', ['job' => $job::class, 'tenant_id' => $tenant->id]);

            return;
        }

        $next($job);
    }
}
