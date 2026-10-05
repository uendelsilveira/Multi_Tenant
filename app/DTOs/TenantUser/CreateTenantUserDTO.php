<?php

declare(strict_types=1);

namespace App\DTOs\TenantUser;

final class CreateTenantUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $roleId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            email: (string) $data['email'],
            roleId: (int) $data['role_id'],
        );
    }
}
