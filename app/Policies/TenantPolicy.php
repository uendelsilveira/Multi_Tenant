<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

/**
 * Super admin e admin gerenciam tenants. Manager e operator apenas consultam.
 * Não existe exclusão definitiva pela aplicação.
 */
final class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $this->canManage($user);
    }

    public function restore(User $user, Tenant $tenant): bool
    {
        return $this->canManage($user);
    }

    public function retryProvisioning(User $user, Tenant $tenant): bool
    {
        return $this->canManage($user);
    }

    public function resendProvisionalPassword(User $user, Tenant $tenant): bool
    {
        return $this->canManage($user);
    }

    public function retrySubscription(User $user, Tenant $tenant): bool
    {
        return $this->canManage($user);
    }

    public function changeStatus(User $user, Tenant $tenant): bool
    {
        return $this->canManage($user);
    }

    public function forceDelete(User $user, Tenant $tenant): bool
    {
        return false;
    }

    private function canManage(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }
}
