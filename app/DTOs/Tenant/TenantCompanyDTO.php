<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

use App\Enums\PersonType;

final class TenantCompanyDTO
{
    public function __construct(
        public readonly string $legalName,
        public readonly ?string $tradeName,
        public readonly PersonType $personType,
        public readonly string $document,
        public readonly ?string $stateRegistration,
        public readonly string $contactName,
        public readonly string $contactEmail,
        public readonly string $contactPhone,
        public readonly string $zipCode,
        public readonly string $street,
        public readonly string $number,
        public readonly ?string $complement,
        public readonly string $district,
        public readonly string $city,
        public readonly string $state,
        public readonly ?string $notes,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            legalName: (string) $data['legal_name'],
            tradeName: self::nullable($data['trade_name'] ?? null),
            personType: $data['person_type'] instanceof PersonType
                ? $data['person_type']
                : PersonType::from((string) $data['person_type']),
            document: (string) $data['document'],
            stateRegistration: self::nullable($data['state_registration'] ?? null),
            contactName: (string) $data['contact_name'],
            contactEmail: (string) $data['contact_email'],
            contactPhone: (string) $data['contact_phone'],
            zipCode: (string) $data['zip_code'],
            street: (string) $data['street'],
            number: (string) $data['number'],
            complement: self::nullable($data['complement'] ?? null),
            district: (string) $data['district'],
            city: (string) $data['city'],
            state: (string) $data['state'],
            notes: self::nullable($data['notes'] ?? null),
        );
    }

    /** @return array<string, string|null> */
    public function toAttributes(): array
    {
        return [
            'legal_name' => $this->legalName,
            'trade_name' => $this->tradeName,
            'person_type' => $this->personType->value,
            'document' => $this->document,
            'state_registration' => $this->stateRegistration,
            'contact_name' => $this->contactName,
            'contact_email' => $this->contactEmail,
            'contact_phone' => $this->contactPhone,
            'zip_code' => $this->zipCode,
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'district' => $this->district,
            'city' => $this->city,
            'state' => $this->state,
            'notes' => $this->notes,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
