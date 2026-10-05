<?php

declare(strict_types=1);

namespace App\DTOs\Role;

use App\Enums\TenantUserType;

final class CreateRoleDTO
{
    /** @param list<string> $permissions */
    public function __construct(
        public readonly string $name,
        public readonly TenantUserType $baseType,
        public readonly array $permissions,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            baseType: $data['base_type'] instanceof TenantUserType
                ? $data['base_type']
                : TenantUserType::from((string) $data['base_type']),
            permissions: array_values(array_map(strval(...), (array) ($data['permissions'] ?? []))),
        );
    }
}
