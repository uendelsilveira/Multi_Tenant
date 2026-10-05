<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ProvisioningStatus;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enquanto o ambiente do tenant não está pronto, mostra uma página de espera
 * em vez de deixar a requisição chegar a um banco que ainda não existe (RN30).
 * Precisa rodar logo depois da inicialização da tenancy, antes da sessão.
 */
final class EnsureTenantIsProvisioned
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant instanceof Tenant && $tenant->provisioning_status !== ProvisioningStatus::Ready) {
            return response()
                ->view('tenant.provisioning', status: Response::HTTP_SERVICE_UNAVAILABLE)
                ->header('Retry-After', '30');
        }

        return $next($request);
    }
}
