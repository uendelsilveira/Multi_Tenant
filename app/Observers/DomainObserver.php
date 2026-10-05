<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Domain;
use App\Services\TenantDomainService;

/**
 * Efeito técnico apenas: mantém o cache de resolução de domínio coerente.
 */
final class DomainObserver
{
    public function __construct(
        private readonly TenantDomainService $domains,
    ) {}

    public function saved(Domain $domain): void
    {
        $this->domains->forget($domain->domain);

        $previous = $domain->getOriginal('domain');

        if (is_string($previous) && $previous !== $domain->domain) {
            $this->domains->forget($previous);
        }
    }

    public function deleted(Domain $domain): void
    {
        $this->domains->forget($domain->domain);
    }
}
