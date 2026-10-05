<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Tenant\CreateTenantDTO;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Enums\ProvisioningStatus;
use App\Enums\TenantStatus;
use App\Enums\TenantStatusSource;
use App\Models\Tenant;
use Carbon\CarbonInterface;

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

    /**
     * Grava a nova situação e a trava, e acrescenta a mudança ao histórico, na mesma transação.
     */
    public function changeStatus(
        Tenant $tenant,
        TenantStatus $to,
        TenantStatusSource $source,
        ?int $centralUserId,
        ?string $reason,
        ?CarbonInterface $lockedUntil,
    ): void;

    /** Ao ficar pronto, registra o momento e limpa o erro anterior. */
    public function updateProvisioning(Tenant $tenant, ProvisioningStatus $status, ?string $error = null): void;
}
