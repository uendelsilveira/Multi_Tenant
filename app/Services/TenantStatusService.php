<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\ChangeTenantStatusDTO;
use App\Enums\TenantStatus;
use App\Enums\TenantStatusSource;
use App\Exceptions\Tenant\TenantNotFoundException;
use App\Exceptions\Tenant\TenantStatusException;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Carbon;

/**
 * Único lugar que altera a situação de um tenant, venha a mudança do central
 * ou da cobrança automática. Toda mudança entra no histórico (RF07).
 */
final class TenantStatusService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
    ) {}

    /**
     * Alteração feita pelo central: exige motivo e pode travar a situação até
     * uma data, período em que a cobrança automática não a altera (RN17, RN19).
     */
    public function changeManually(ChangeTenantStatusDTO $dto): Tenant
    {
        $tenant = $this->tenants->find($dto->tenantId) ?? throw TenantNotFoundException::withId($dto->tenantId);

        if ($dto->reason === '') {
            throw TenantStatusException::reasonRequired();
        }

        if ($dto->lockedUntil !== null && ! $dto->lockedUntil->isFuture()) {
            throw TenantStatusException::lockMustBeInTheFuture();
        }

        $sameStatus = $tenant->status === $dto->status;
        $sameLock = $this->currentLock($tenant)?->getTimestamp() === $dto->lockedUntil?->getTimestamp();

        if ($sameStatus && $sameLock) {
            throw TenantStatusException::nothingToChange();
        }

        $this->tenants->changeStatus($tenant, $dto->status, TenantStatusSource::Manual, $dto->centralUserId, $dto->reason, $dto->lockedUntil);

        return $tenant;
    }

    /**
     * Alteração vinda da cobrança automática. Não faz nada, e devolve false,
     * se a situação estiver travada pelo central ou já for a pedida.
     */
    public function changeFromGateway(string $tenantId, TenantStatus $status, string $reason): bool
    {
        $tenant = $this->tenants->find($tenantId) ?? throw TenantNotFoundException::withId($tenantId);

        if ($this->currentLock($tenant) !== null || $tenant->status === $status) {
            return false;
        }

        $this->tenants->changeStatus($tenant, $status, TenantStatusSource::Gateway, null, $reason, null);

        return true;
    }

    public function isSuspended(Tenant $tenant): bool
    {
        return $tenant->status === TenantStatus::Suspended;
    }

    /** A trava só vale enquanto a data não passou. */
    private function currentLock(Tenant $tenant): ?Carbon
    {
        $lock = $tenant->status_locked_until;

        return $lock !== null && $lock->isFuture() ? $lock : null;
    }
}
