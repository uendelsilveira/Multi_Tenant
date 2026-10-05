<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TenantUser\TenantUserRuleException;
use App\Models\Role;
use App\Models\TenantUser;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TenantUserRepositoryInterface;

/**
 * O que cada perfil permite, e a garantia de que o tenant nunca fica sem
 * alguém capaz de gerenciar pessoas.
 */
final class TenantAccessService
{
    public function __construct(
        private readonly PermissionCatalog $catalog,
        private readonly RoleRepositoryInterface $roles,
        private readonly TenantUserRepositoryInterface $users,
    ) {}

    /**
     * Perfil de sistema tem todas as permissões do seu tipo base. Perfil
     * customizado tem as que foram marcadas e ainda se aplicam ao tipo dele.
     *
     * @return list<string>
     */
    public function permissionsOf(Role $role): array
    {
        if ($role->base_type === null) {
            return [];
        }

        if ($role->is_system) {
            return $this->catalog->keysFor($role->base_type);
        }

        return $this->catalog->applicable($role->permissions ?? [], $role->base_type);
    }

    public function allows(TenantUser $user, string $permission): bool
    {
        return $user->is_active
            && $user->role !== null
            && in_array($permission, $this->permissionsOf($user->role), true);
    }

    /**
     * Confere se, depois de uma alteração, ainda resta alguém ativo que possa
     * gerenciar pessoas.
     *
     * @param  int|null  $exceptUserId  pessoa que está saindo da conta (desativada ou trocando de perfil)
     * @param  int|null  $exceptRoleId  perfil que está deixando de dar a permissão (alterado)
     */
    public function assertPeopleManagerRemains(?int $exceptUserId = null, ?int $exceptRoleId = null): void
    {
        $roleIds = [];

        foreach ($this->roles->all() as $role) {
            if ($role->id !== $exceptRoleId && in_array(PermissionCatalog::PEOPLE_MANAGE, $this->permissionsOf($role), true)) {
                $roleIds[] = $role->id;
            }
        }

        if ($roleIds === [] || $this->users->countActiveInRoles($roleIds, $exceptUserId) === 0) {
            throw TenantUserRuleException::lastPeopleManager();
        }
    }
}
