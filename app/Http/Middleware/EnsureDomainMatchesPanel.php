<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\DomainPanel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cada domínio serve um único painel (RN01). O caminho de outro painel,
 * acessado por este domínio, não existe.
 */
final class EnsureDomainMatchesPanel
{
    public function handle(Request $request, Closure $next, string $panel): Response
    {
        $resolved = $request->attributes->get(InitializeTenancyForTenantDomain::PANEL_ATTRIBUTE);

        if (! $resolved instanceof DomainPanel || $resolved->value !== $panel) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $next($request);
    }
}
