<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\TenantUserType;
use App\Models\TenantUser;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use Carbon\CarbonInterface;
use SensitiveParameter;

final class TenantUserRepository implements TenantUserRepositoryInterface
{
    public function findInitialAdmin(): ?TenantUser
    {
        return TenantUser::query()
            ->where('type', TenantUserType::Admin->value)
            ->orderBy('id')
            ->first();
    }

    public function createInitialAdmin(
        string $name,
        string $email,
        #[SensitiveParameter] string $plainPassword,
        CarbonInterface $expiresAt,
    ): TenantUser {
        // O cast `hashed` do model grava apenas o hash.
        return TenantUser::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $plainPassword,
            'type' => TenantUserType::Admin->value,
            'must_change_password' => true,
            'password_expires_at' => $expiresAt,
        ]);
    }

    public function setProvisionalPassword(
        TenantUser $user,
        #[SensitiveParameter] string $plainPassword,
        CarbonInterface $expiresAt,
    ): void {
        $user->update([
            'password' => $plainPassword,
            'must_change_password' => true,
            'password_expires_at' => $expiresAt,
        ]);
    }

    public function changePassword(TenantUser $user, #[SensitiveParameter] string $plainPassword): void
    {
        $user->update([
            'password' => $plainPassword,
            'must_change_password' => false,
            'password_expires_at' => null,
        ]);
    }
}
