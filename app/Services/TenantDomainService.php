<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DnsLookupInterface;
use App\DTOs\TenantDomain\ResolvedTenantDomainDTO;
use App\Enums\DomainPanel;
use App\Enums\DomainStatus;
use App\Exceptions\TenantDomain\TenantDomainAlreadyVerifiedException;
use App\Exceptions\TenantDomain\TenantDomainNotFoundException;
use App\Models\Domain;
use App\Repositories\Contracts\DomainRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as Cache;

final class TenantDomainService
{
    /** Tempo, em segundos, que um domínio verificado fica em cache (RNF02). */
    public const CACHE_SECONDS = 300;

    private const CACHE_PREFIX = 'tenant-domain:';

    public function __construct(
        private readonly DomainRepositoryInterface $domains,
        private readonly TenantRepositoryInterface $tenants,
        private readonly DnsLookupInterface $dns,
        private readonly Cache $cache,
    ) {}

    /**
     * Diz a qual tenant e painel um host pertence. Só domínio verificado de
     * tenant não excluído resolve; o resto se comporta como inexistente (RN02).
     */
    public function resolve(string $host): ?ResolvedTenantDomainDTO
    {
        $cached = $this->cache->get(self::CACHE_PREFIX.$host);

        if (! is_array($cached)) {
            $domain = $this->domains->findByHost($host);

            if ($domain === null || $domain->status !== DomainStatus::Active) {
                return null;
            }

            $cached = ['tenant_id' => $domain->tenant_id, 'panel' => $domain->panel->value];

            $this->cache->put(self::CACHE_PREFIX.$host, $cached, self::CACHE_SECONDS);
        }

        $tenant = $this->tenants->find((string) $cached['tenant_id']);
        $panel = DomainPanel::tryFrom((string) $cached['panel']);

        if ($tenant === null || $panel === null) {
            return null;
        }

        return new ResolvedTenantDomainDTO($tenant, $panel);
    }

    /** Descarta o que estiver em cache para o host. */
    public function forget(string $host): void
    {
        $this->cache->forget(self::CACHE_PREFIX.$host);
    }

    public function verify(int $domainId, int $centralUserId): Domain
    {
        $domain = $this->findOrFail($domainId);

        if ($domain->status === DomainStatus::Active) {
            throw TenantDomainAlreadyVerifiedException::forHost($domain->domain);
        }

        $this->domains->markVerified($domain, $centralUserId);

        return $domain;
    }

    /** @return list<string> */
    public function dnsRecords(int $domainId): array
    {
        return $this->dns->lookup($this->findOrFail($domainId)->domain);
    }

    private function findOrFail(int $domainId): Domain
    {
        return $this->domains->find($domainId) ?? throw TenantDomainNotFoundException::withId($domainId);
    }
}
