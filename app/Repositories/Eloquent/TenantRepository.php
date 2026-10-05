<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Tenant\CreateTenantDTO;
use App\DTOs\Tenant\TenantDomainDTO;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Enums\DomainStatus;
use App\Enums\ProvisioningStatus;
use App\Enums\TenantStatus;
use App\Models\Domain;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;

final class TenantRepository implements TenantRepositoryInterface
{
    /**
     * Grava só o cadastro, no banco central. O banco do tenant é criado depois,
     * pelo provisionamento, fora desta transação.
     */
    public function create(CreateTenantDTO $dto): Tenant
    {
        return (new Tenant)->getConnection()->transaction(function () use ($dto): Tenant {
            /** @var Tenant $tenant */
            $tenant = Tenant::query()->create([
                'id' => $dto->slug,
                ...$dto->company->toAttributes(),
                'plan_id' => $dto->planId,
                'billing_cycle' => $dto->billingCycle->value,
                'status' => TenantStatus::Active->value,
                'provisioning_status' => ProvisioningStatus::Pending->value,
            ]);

            foreach ($dto->domains as $domain) {
                $tenant->domains()->create([
                    'domain' => $domain->host,
                    'panel' => $domain->panel->value,
                    'status' => DomainStatus::Pending->value,
                ]);
            }

            return $tenant->load('domains');
        });
    }

    public function update(Tenant $tenant, UpdateTenantDTO $dto): Tenant
    {
        return $tenant->getConnection()->transaction(function () use ($tenant, $dto): Tenant {
            $tenant->update([
                ...$dto->company->toAttributes(),
                'plan_id' => $dto->planId,
                'billing_cycle' => $dto->billingCycle->value,
            ]);

            $this->syncDomains($tenant, $dto->domains);

            return $tenant->load('domains');
        });
    }

    public function softDelete(Tenant $tenant): void
    {
        $tenant->delete();
    }

    public function restore(Tenant $tenant): void
    {
        $tenant->restore();
    }

    public function find(string $id, bool $withTrashed = false): ?Tenant
    {
        $query = $withTrashed ? Tenant::withTrashed() : Tenant::query();

        return $query->with('domains')->find($id);
    }

    public function slugExists(string $slug): bool
    {
        return Tenant::withTrashed()->whereKey($slug)->exists();
    }

    public function documentExists(string $document, ?string $exceptTenantId = null): bool
    {
        return Tenant::withTrashed()
            ->where('document', $document)
            ->when($exceptTenantId !== null, fn ($query) => $query->whereKeyNot($exceptTenantId))
            ->exists();
    }

    public function hostsInUse(array $hosts, ?string $exceptTenantId = null): array
    {
        $inUse = Domain::query()
            ->whereIn('domain', $hosts)
            ->when($exceptTenantId !== null, fn ($query) => $query->where('tenant_id', '!=', $exceptTenantId))
            ->toBase()
            ->pluck('domain');

        return array_values(array_map(strval(...), $inUse->all()));
    }

    public function updateProvisioning(Tenant $tenant, ProvisioningStatus $status, ?string $error = null): void
    {
        $tenant->update([
            'provisioning_status' => $status->value,
            'provisioning_error' => $status === ProvisioningStatus::Failed ? $error : null,
            'provisioned_at' => $status === ProvisioningStatus::Ready ? now() : $tenant->provisioned_at,
        ]);
    }

    /** @param list<TenantDomainDTO> $domains */
    private function syncDomains(Tenant $tenant, array $domains): void
    {
        $hosts = array_map(fn (TenantDomainDTO $domain): string => $domain->host, $domains);

        // Um a um, pelo model: é o que dispara a limpeza do cache de resolução.
        $tenant->domains()->whereNotIn('domain', $hosts)->get()->each->delete();

        foreach ($domains as $domain) {
            /** @var Domain|null $existing */
            $existing = $tenant->domains()->where('domain', $domain->host)->first();

            if ($existing !== null) {
                $existing->update(['panel' => $domain->panel->value]);

                continue;
            }

            $tenant->domains()->create([
                'domain' => $domain->host,
                'panel' => $domain->panel->value,
                'status' => DomainStatus::Pending->value,
            ]);
        }
    }
}
