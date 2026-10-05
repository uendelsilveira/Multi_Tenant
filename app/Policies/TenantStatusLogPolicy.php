<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantStatusLog;
use App\Models\User;

/**
 * O histórico de situação é só para consulta: nasce das mudanças e nunca é editado.
 */
final class TenantStatusLogPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TenantStatusLog $log): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TenantStatusLog $log): bool
    {
        return false;
    }

    public function delete(User $user, TenantStatusLog $log): bool
    {
        return false;
    }
}
