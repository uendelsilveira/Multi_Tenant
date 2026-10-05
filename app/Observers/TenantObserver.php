<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Tenant;
use App\Services\TenantDomainService;

/**
 * Efeito técnico apenas: ao excluir um tenant, seus domínios saem do cache de
 * resolução na hora, sem esperar o prazo de expiração.
 */
final class TenantObserver
{
    public function __construct(
        private readonly TenantDomainService $domains,
    ) {}

    public function deleted(Tenant $tenant): void
    {
        foreach ($tenant->domains as $domain) {
            $this->domains->forget($domain->domain);
        }
    }
}
