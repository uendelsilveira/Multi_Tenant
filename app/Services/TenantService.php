<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CreateTenantDTO;
use App\DTOs\Tenant\TenantCompanyDTO;
use App\DTOs\Tenant\TenantDomainDTO;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Enums\BillingCycle;
use App\Enums\DomainPanel;
use App\Exceptions\Plan\PlanNotAvailableException;
use App\Exceptions\Plan\PlanNotFoundException;
use App\Exceptions\Tenant\InvalidTenantDataException;
use App\Exceptions\Tenant\InvalidTenantDomainsException;
use App\Exceptions\Tenant\TenantAlreadyExistsException;
use App\Exceptions\Tenant\TenantNotFoundException;
use App\Models\PlanPrice;
use App\Models\Tenant;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;

final class TenantService
{
    private const SLUG_PATTERN = '/^[a-z][a-z0-9-]{2,49}$/';

    private const HOST_PATTERN = '/^(?=.{4,253}$)[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/';

    /** @param list<string> $centralDomains */
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly PlanRepositoryInterface $plans,
        private readonly DocumentValidator $documents,
        private readonly array $centralDomains,
    ) {}

    public function create(CreateTenantDTO $dto): Tenant
    {
        if (preg_match(self::SLUG_PATTERN, $dto->slug) !== 1) {
            throw InvalidTenantDataException::slugFormat($dto->slug);
        }

        if ($this->tenants->slugExists($dto->slug)) {
            throw TenantAlreadyExistsException::withSlug($dto->slug);
        }

        $this->assertValidCompany($dto->company, null);
        $this->assertPlanAvailable($dto->planId, $dto->billingCycle, null);
        $this->assertValidDomains($dto->domains, null);

        return $this->tenants->create($dto);
    }

    public function update(UpdateTenantDTO $dto): Tenant
    {
        $tenant = $this->tenants->find($dto->tenantId) ?? throw TenantNotFoundException::withId($dto->tenantId);

        $this->assertValidCompany($dto->company, $tenant->id);
        $this->assertPlanAvailable($dto->planId, $dto->billingCycle, $tenant->plan_id);
        $this->assertValidDomains($dto->domains, $tenant->id);

        return $this->tenants->update($tenant, $dto);
    }

    public function planIdOf(string $tenantId): ?int
    {
        return $this->tenants->find($tenantId)?->plan_id;
    }

    public function billingCycleOf(string $tenantId): ?BillingCycle
    {
        return $this->tenants->find($tenantId)?->billing_cycle;
    }

    /** Exclusão lógica: o banco do tenant é mantido e o slug continua reservado. */
    public function softDelete(string $tenantId): Tenant
    {
        $tenant = $this->tenants->find($tenantId) ?? throw TenantNotFoundException::withId($tenantId);

        $this->tenants->softDelete($tenant);

        return $tenant;
    }

    public function restore(string $tenantId): Tenant
    {
        $tenant = $this->tenants->find($tenantId, withTrashed: true) ?? throw TenantNotFoundException::withId($tenantId);

        if ($tenant->trashed()) {
            $this->tenants->restore($tenant);
        }

        return $tenant;
    }

    private function assertValidCompany(TenantCompanyDTO $company, ?string $exceptTenantId): void
    {
        if (! $this->documents->isValid($company->personType, $company->document)) {
            throw InvalidTenantDataException::document($company->document);
        }

        if ($this->tenants->documentExists($company->document, $exceptTenantId)) {
            throw TenantAlreadyExistsException::withDocument($company->document);
        }
    }

    /**
     * Um plano inativo não pode ser contratado, mas o tenant que já está nele
     * pode permanecer. O ciclo precisa ser um dos que o plano oferece.
     */
    private function assertPlanAvailable(int $planId, BillingCycle $cycle, ?int $currentPlanId): void
    {
        $plan = $this->plans->find($planId) ?? throw PlanNotFoundException::withId($planId);

        if (! $plan->is_active && $plan->id !== $currentPlanId) {
            throw PlanNotAvailableException::inactive($plan->name);
        }

        $offersCycle = $plan->prices->contains(
            fn (PlanPrice $price): bool => $price->billing_cycle === $cycle,
        );

        if (! $offersCycle) {
            throw PlanNotAvailableException::cycleNotOffered($plan->name, $cycle);
        }
    }

    /** @param list<TenantDomainDTO> $domains */
    private function assertValidDomains(array $domains, ?string $exceptTenantId): void
    {
        if ($domains === []) {
            throw InvalidTenantDomainsException::none();
        }

        $hosts = [];
        $hasAdminPanel = false;

        foreach ($domains as $domain) {
            if (preg_match(self::HOST_PATTERN, $domain->host) !== 1) {
                throw InvalidTenantDomainsException::invalidHost($domain->host);
            }

            if (in_array($domain->host, $this->centralDomains, true)) {
                throw InvalidTenantDomainsException::central($domain->host);
            }

            if (in_array($domain->host, $hosts, true)) {
                throw InvalidTenantDomainsException::duplicated($domain->host);
            }

            $hosts[] = $domain->host;
            $hasAdminPanel = $hasAdminPanel || $domain->panel === DomainPanel::Admin;
        }

        if (! $hasAdminPanel) {
            throw InvalidTenantDomainsException::missingAdminPanel();
        }

        $inUse = $this->tenants->hostsInUse($hosts, $exceptTenantId);

        if ($inUse !== []) {
            throw InvalidTenantDomainsException::alreadyInUse($inUse[0]);
        }
    }
}
