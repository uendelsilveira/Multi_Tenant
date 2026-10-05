<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Role\CreateRoleDTO;
use App\DTOs\Role\UpdateRoleDTO;
use App\Enums\TenantUserType;
use App\Models\Role;
use Illuminate\Support\Collection;

/**
 * Opera no banco do tenant: só pode ser usado dentro do contexto de um tenant.
 */
interface RoleRepositoryInterface
{
    /** @param list<string> $permissions já filtradas pelo catálogo */
    public function create(CreateRoleDTO $dto, array $permissions): Role;

    /** @param list<string> $permissions já filtradas pelo catálogo */
    public function update(Role $role, UpdateRoleDTO $dto, array $permissions): Role;

    public function delete(Role $role): void;

    public function find(int $id): ?Role;

    /** @return Collection<int, Role> */
    public function all(): Collection;

    public function systemRole(TenantUserType $type): ?Role;

    public function nameExists(string $name, ?int $exceptId = null): bool;

    public function hasUsers(int $roleId): bool;

    /**
     * Perfis dos tipos indicados, id => "Nome (Tipo)", para seleção.
     *
     * @param  list<TenantUserType>  $types
     * @return array<int, string>
     */
    public function optionsForTypes(array $types): array;
}
