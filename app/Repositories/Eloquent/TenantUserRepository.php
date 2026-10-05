<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\TenantUser\CreateTenantUserDTO;
use App\DTOs\TenantUser\UpdateTenantUserDTO;
use App\Enums\TenantUserType;
use App\Models\Role;
use App\Models\TenantUser;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use SensitiveParameter;

final class TenantUserRepository implements TenantUserRepositoryInterface
{
    public function find(int $id): ?TenantUser
    {
        return TenantUser::query()->with('role')->find($id);
    }

    public function findInitialAdmin(): ?TenantUser
    {
        return TenantUser::query()
            ->with('role')
            ->whereHas('role', fn ($query) => $query->where('base_type', TenantUserType::Admin->value))
            ->orderBy('id')
            ->first();
    }

    public function createInitialAdmin(
        string $name,
        string $email,
        #[SensitiveParameter] string $plainPassword,
        CarbonInterface $expiresAt,
    ): TenantUser {
        $roleId = Role::query()
            ->where('is_system', true)
            ->where('base_type', TenantUserType::Admin->value)
            ->valueOrFail('id');

        // O cast `hashed` do model grava apenas o hash.
        return TenantUser::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $plainPassword,
            'role_id' => $roleId,
            'is_active' => true,
            'must_change_password' => true,
            'password_expires_at' => $expiresAt,
        ])->load('role');
    }

    public function createAwaitingAccess(CreateTenantUserDTO $dto): TenantUser
    {
        return TenantUser::query()->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Str::password(40),
            'role_id' => $dto->roleId,
            'is_active' => true,
            'must_change_password' => true,
            'password_expires_at' => now(),
        ])->load('role');
    }

    public function update(TenantUser $user, UpdateTenantUserDTO $dto): TenantUser
    {
        $user->update([
            'name' => $dto->name,
            'email' => $dto->email,
            'role_id' => $dto->roleId,
        ]);

        return $user->load('role');
    }

    public function setActive(TenantUser $user, bool $active): void
    {
        $user->update(['is_active' => $active]);
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        return TenantUser::query()
            ->where('email', $email)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function countActiveInRoles(array $roleIds, ?int $exceptUserId = null): int
    {
        return TenantUser::query()
            ->where('is_active', true)
            ->whereIn('role_id', $roleIds)
            ->when($exceptUserId !== null, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->count();
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
