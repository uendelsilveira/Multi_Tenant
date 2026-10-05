<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

final class UpdateCustomerDTO
{
    public function __construct(
        public readonly int $customerId,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $document,
        public readonly ?string $notes,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(int $customerId, array $data): self
    {
        return new self(
            customerId: $customerId,
            name: (string) $data['name'],
            email: (string) $data['email'],
            phone: self::nullable($data['phone'] ?? null),
            document: self::nullable($data['document'] ?? null),
            notes: self::nullable($data['notes'] ?? null),
        );
    }

    private static function nullable(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
