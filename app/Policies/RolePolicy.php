<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\TenantUser;
use App\Services\PermissionCatalog;
use App\Services\TenantAccessService;

/**
 * Perfis são geridos por quem tem a permissão "Gerenciar perfis".
 * Esconder alterar e excluir de perfil de sistema é dica de interface; a regra está no Service.
 */
final class RolePolicy
{
    public function __construct(
        private readonly TenantAccessService $access,
    ) {}

    public function viewAny(TenantUser $actor): bool
    {
        return $this->canManage($actor);
    }

    public function view(TenantUser $actor, Role $role): bool
    {
        return $this->canManage($actor);
    }

    public function create(TenantUser $actor): bool
    {
        return $this->canManage($actor);
    }

    public function update(TenantUser $actor, Role $role): bool
    {
        return ! $role->is_system && $this->canManage($actor);
    }

    public function delete(TenantUser $actor, Role $role): bool
    {
        return ! $role->is_system && $this->canManage($actor);
    }

    private function canManage(TenantUser $actor): bool
    {
        return $this->access->allows($actor, PermissionCatalog::ROLES_MANAGE);
    }
}
