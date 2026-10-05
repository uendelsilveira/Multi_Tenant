<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TenantDomainService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve o tenant e o painel pelo domínio da requisição (RF08).
 * Em domínio central não faz nada, o que permite usá-lo em rotas compartilhadas
 * como a de atualização do Livewire. Domínio desconhecido, pendente ou de tenant
 * excluído responde 404.
 */
final class InitializeTenancyForTenantDomain
{
    /** Atributo da requisição com o App\Enums\DomainPanel resolvido. */
    public const PANEL_ATTRIBUTE = 'tenant_domain_panel';

    public function __construct(
        private readonly TenantDomainService $domains,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (in_array($host, (array) config('tenancy.central_domains', []), true)) {
            return $next($request);
        }

        $resolved = $this->domains->resolve($host) ?? abort(Response::HTTP_NOT_FOUND);

        tenancy()->initialize($resolved->tenant);

        $request->attributes->set(self::PANEL_ATTRIBUTE, $resolved->panel);

        return $next($request);
    }
}
