<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

use App\Enums\BillingCycle;

final class UpdateTenantDTO
{
    /** @param list<TenantDomainDTO> $domains */
    public function __construct(
        public readonly string $tenantId,
        public readonly TenantCompanyDTO $company,
        public readonly int $planId,
        public readonly BillingCycle $billingCycle,
        public readonly array $domains,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(string $tenantId, array $data): self
    {
        return new self(
            tenantId: $tenantId,
            company: TenantCompanyDTO::fromArray($data),
            planId: (int) $data['plan_id'],
            billingCycle: $data['billing_cycle'] instanceof BillingCycle
                ? $data['billing_cycle']
                : BillingCycle::from((string) $data['billing_cycle']),
            domains: TenantDomainDTO::listFromArray((array) ($data['domains'] ?? [])),
        );
    }
}
