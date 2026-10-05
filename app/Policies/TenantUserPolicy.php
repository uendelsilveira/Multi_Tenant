<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantUser;
use App\Services\PermissionCatalog;
use App\Services\TenantAccessService;

/**
 * Pessoas do tenant são geridas por quem tem a permissão "Gerenciar pessoas".
 */
final class TenantUserPolicy
{
    public function __construct(
        private readonly TenantAccessService $access,
    ) {}

    public function viewAny(TenantUser $actor): bool
    {
        return $this->canManage($actor);
    }

    public function view(TenantUser $actor, TenantUser $person): bool
    {
        return $this->canManage($actor);
    }

    public function create(TenantUser $actor): bool
    {
        return $this->canManage($actor);
    }

    public function update(TenantUser $actor, TenantUser $person): bool
    {
        return $this->canManage($actor);
    }

    public function deactivate(TenantUser $actor, TenantUser $person): bool
    {
        return $this->canManage($actor);
    }

    public function activate(TenantUser $actor, TenantUser $person): bool
    {
        return $this->canManage($actor);
    }

    public function resendProvisionalPassword(TenantUser $actor, TenantUser $person): bool
    {
        return $this->canManage($actor);
    }

    /** Pessoa não é excluída, só desativada. */
    public function delete(TenantUser $actor, TenantUser $person): bool
    {
        return false;
    }

    private function canManage(TenantUser $actor): bool
    {
        return $this->access->allows($actor, PermissionCatalog::PEOPLE_MANAGE);
    }
}
