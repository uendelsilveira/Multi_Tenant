<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

/**
 * Super admin e admin gerenciam planos. Manager e operator apenas consultam.
 */
final class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Plan $plan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Plan $plan): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }
}
