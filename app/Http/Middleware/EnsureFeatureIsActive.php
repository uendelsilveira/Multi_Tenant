<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TenantFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rota de uma funcionalidade inativa não existe para o tenant (RF15).
 * Uso: ->middleware(EnsureFeatureIsActive::class.':helpdesk.tickets')
 */
final class EnsureFeatureIsActive
{
    public function __construct(
        private readonly TenantFeatureService $features,
    ) {}

    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        if (! $this->features->isActiveForCurrentTenant($featureKey)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $next($request);
    }
}
