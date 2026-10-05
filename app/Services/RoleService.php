<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Role\CreateRoleDTO;
use App\DTOs\Role\UpdateRoleDTO;
use App\Enums\TenantUserType;
use App\Exceptions\Role\RoleNotFoundException;
use App\Exceptions\Role\RoleRuleException;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;

final class RoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly PermissionCatalog $catalog,
        private readonly TenantAccessService $access,
    ) {}

    public function create(CreateRoleDTO $dto): Role
    {
        if ($this->roles->nameExists($dto->name)) {
            throw RoleRuleException::nameTaken($dto->name);
        }

        return $this->roles->create($dto, $this->catalog->applicable($dto->permissions, $dto->baseType));
    }

    /**
     * O tipo base pode mudar. Quando muda, as pessoas do perfil passam a
     * entrar em outro painel, e por isso a alteração é recusada se deixaria o
     * tenant sem ninguém que gerencie pessoas.
     */
    public function update(UpdateRoleDTO $dto): Role
    {
        $role = $this->findOrFail($dto->roleId);

        if ($role->is_system) {
            throw RoleRuleException::systemRole($role->name);
        }

        if ($this->roles->nameExists($dto->name, $role->id)) {
            throw RoleRuleException::nameTaken($dto->name);
        }

        // Ao trocar o tipo base, o que não se aplica ao novo tipo é descartado.
        $permissions = $this->catalog->applicable($dto->permissions, $dto->baseType);

        $managedPeople = in_array(PermissionCatalog::PEOPLE_MANAGE, $this->access->permissionsOf($role), true);
        $willManagePeople = in_array(PermissionCatalog::PEOPLE_MANAGE, $permissions, true);

        if ($managedPeople && ! $willManagePeople) {
            $this->access->assertPeopleManagerRemains(exceptRoleId: $role->id);
        }

        return $this->roles->update($role, $dto, $permissions);
    }

    public function delete(int $roleId): void
    {
        $role = $this->findOrFail($roleId);

        if ($role->is_system) {
            throw RoleRuleException::systemRole($role->name);
        }

        if ($this->roles->hasUsers($role->id)) {
            throw RoleRuleException::inUse($role->name);
        }

        $this->roles->delete($role);
    }

    /**
     * Perfis que podem ser atribuídos no cadastro de pessoas (clientes ficam de fora).
     *
     * @return array<int, string>
     */
    public function assignableOptions(): array
    {
        return $this->roles->optionsForTypes([TenantUserType::Admin, TenantUserType::User]);
    }

    /** @return array<string, string> */
    public function permissionOptions(TenantUserType $type): array
    {
        return $this->catalog->optionsFor($type);
    }

    /** @return list<string> */
    public function permissionsOf(Role $role): array
    {
        return $this->access->permissionsOf($role);
    }

    private function findOrFail(int $roleId): Role
    {
        return $this->roles->find($roleId) ?? throw RoleNotFoundException::withId($roleId);
    }
}
