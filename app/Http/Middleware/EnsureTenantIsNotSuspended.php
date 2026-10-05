<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantStatusService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant suspenso fica inteiramente bloqueado (RN16): qualquer acesso aos
 * painéis dele recebe a página que pede contato com o administrador.
 * Roda logo depois de identificar o tenant, antes da sessão e do login.
 */
final class EnsureTenantIsNotSuspended
{
    public function __construct(
        private readonly TenantStatusService $status,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant instanceof Tenant && $this->status->isSuspended($tenant)) {
            return response()->view('tenant.suspended', status: Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
