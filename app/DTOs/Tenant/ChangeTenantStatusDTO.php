<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

use App\Enums\TenantStatus;
use Carbon\CarbonImmutable;

final class ChangeTenantStatusDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly TenantStatus $status,
        public readonly string $reason,
        public readonly ?CarbonImmutable $lockedUntil,
        public readonly int $centralUserId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(string $tenantId, int $centralUserId, array $data): self
    {
        $lockedUntil = $data['locked_until'] ?? null;

        return new self(
            tenantId: $tenantId,
            status: $data['status'] instanceof TenantStatus ? $data['status'] : TenantStatus::from((string) $data['status']),
            reason: trim((string) ($data['reason'] ?? '')),
            lockedUntil: $lockedUntil === null || $lockedUntil === '' ? null : CarbonImmutable::parse((string) $lockedUntil),
            centralUserId: $centralUserId,
        );
    }
}
