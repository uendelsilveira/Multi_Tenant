<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Role\CreateRoleDTO;
use App\DTOs\Role\UpdateRoleDTO;
use App\Enums\TenantUserType;
use App\Models\Role;
use App\Models\TenantUser;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Collection;

final class RoleRepository implements RoleRepositoryInterface
{
    public function create(CreateRoleDTO $dto, array $permissions): Role
    {
        return Role::query()->create([
            'name' => $dto->name,
            'base_type' => $dto->baseType->value,
            'is_system' => false,
            'permissions' => $permissions,
        ]);
    }

    public function update(Role $role, UpdateRoleDTO $dto, array $permissions): Role
    {
        $role->update([
            'name' => $dto->name,
            'base_type' => $dto->baseType->value,
            'permissions' => $permissions,
        ]);

        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    public function find(int $id): ?Role
    {
        return Role::query()->find($id);
    }

    public function all(): Collection
    {
        return Role::query()->orderBy('name')->get()->toBase();
    }

    public function systemRole(TenantUserType $type): ?Role
    {
        return Role::query()
            ->where('is_system', true)
            ->where('base_type', $type->value)
            ->first();
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        return Role::query()
            ->where('name', $name)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function hasUsers(int $roleId): bool
    {
        return TenantUser::query()->where('role_id', $roleId)->exists();
    }

    public function optionsForTypes(array $types): array
    {
        $values = array_map(fn (TenantUserType $type): string => $type->value, $types);
        $options = [];

        foreach (Role::query()->whereIn('base_type', $values)->orderBy('name')->get() as $role) {
            $label = $role->base_type?->label();

            $options[$role->id] = $label === null || $label === $role->name ? $role->name : "{$role->name} ({$label})";
        }

        return $options;
    }
}
