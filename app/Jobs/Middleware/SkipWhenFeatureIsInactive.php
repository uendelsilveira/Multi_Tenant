<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use App\Services\TenantFeatureService;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Job de uma funcionalidade inativa não executa (RF15): termina sem fazer nada
 * e sem contar como falha. Cobre também o job enfileirado antes de a
 * funcionalidade ser desligada ou sair do plano.
 *
 * Uso, dentro do job: public function middleware(): array { return [new SkipWhenFeatureIsInactive('helpdesk.tickets')]; }
 */
final class SkipWhenFeatureIsInactive
{
    public function __construct(
        private readonly string $featureKey,
    ) {}

    public function handle(object $job, Closure $next): void
    {
        if (! app(TenantFeatureService::class)->isActiveForCurrentTenant($this->featureKey)) {
            Log::info('job.skipped_feature_inactive', ['job' => $job::class, 'feature' => $this->featureKey]);

            return;
        }

        $next($job);
    }
}
