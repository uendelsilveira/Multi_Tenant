<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Domain;
use App\Models\User;

/**
 * Domínios são cadastrados e removidos pelo cadastro do tenant. Aqui o central
 * só consulta e verifica. Super admin e admin verificam; os demais consultam.
 */
final class DomainPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Domain $domain): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Domain $domain): bool
    {
        return false;
    }

    public function delete(User $user, Domain $domain): bool
    {
        return false;
    }

    public function verify(User $user, Domain $domain): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }
}
