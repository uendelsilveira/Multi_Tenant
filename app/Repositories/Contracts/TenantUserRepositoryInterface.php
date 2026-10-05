<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\TenantUser\CreateTenantUserDTO;
use App\DTOs\TenantUser\UpdateTenantUserDTO;
use App\Models\TenantUser;
use Carbon\CarbonInterface;
use SensitiveParameter;

/**
 * Opera no banco do tenant: só pode ser usado dentro do contexto de um tenant.
 */
interface TenantUserRepositoryInterface
{
    public function find(int $id): ?TenantUser;

    /** O admin mais antigo do tenant. */
    public function findInitialAdmin(): ?TenantUser;

    /** Cria o admin inicial, no perfil de sistema Admin. */
    public function createInitialAdmin(
        string $name,
        string $email,
        #[SensitiveParameter] string $plainPassword,
        CarbonInterface $expiresAt,
    ): TenantUser;

    /**
     * Cria a pessoa sem acesso utilizável: a senha gravada é aleatória e já
     * nasce vencida. O acesso vem depois, com a senha provisória.
     */
    public function createAwaitingAccess(CreateTenantUserDTO $dto): TenantUser;

    public function update(TenantUser $user, UpdateTenantUserDTO $dto): TenantUser;

    public function setActive(TenantUser $user, bool $active): void;

    public function emailExists(string $email, ?int $exceptId = null): bool;

    /**
     * Quantas pessoas ativas têm um dos perfis indicados.
     *
     * @param  list<int>  $roleIds
     */
    public function countActiveInRoles(array $roleIds, ?int $exceptUserId = null): int;

    public function setProvisionalPassword(
        TenantUser $user,
        #[SensitiveParameter] string $plainPassword,
        CarbonInterface $expiresAt,
    ): void;

    /** Grava a senha definitiva e encerra a pendência de troca. */
    public function changePassword(TenantUser $user, #[SensitiveParameter] string $plainPassword): void;
}
