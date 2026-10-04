<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Tenant\CreateTenantDTO;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Models\Tenant;

interface TenantRepositoryInterface
{
    public function create(CreateTenantDTO $dto): Tenant;

    public function update(Tenant $tenant, UpdateTenantDTO $dto): Tenant;

    public function softDelete(Tenant $tenant): void;

    public function restore(Tenant $tenant): void;

    public function find(string $id, bool $withTrashed = false): ?Tenant;

    /** Considera também tenants excluídos: o banco deles ainda existe. */
    public function slugExists(string $slug): bool;

    /** Considera também tenants excluídos. */
    public function documentExists(string $document, ?string $exceptTenantId = null): bool;

    /**
     * @param  list<string>  $hosts
     * @return list<string> hosts já cadastrados em outro tenant
     */
    public function hostsInUse(array $hosts, ?string $exceptTenantId = null): array;
}
