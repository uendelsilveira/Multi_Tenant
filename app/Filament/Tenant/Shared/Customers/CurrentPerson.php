<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Shared\Customers;

use App\Models\TenantUser;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A pessoa autenticada no painel de tenant atual.
 */
final class CurrentPerson
{
    public static function get(): TenantUser
    {
        /** @var Authenticatable|null $user */
        $user = Filament::auth()->user();

        abort_unless($user instanceof TenantUser, 403);

        return $user;
    }
}
