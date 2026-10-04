<?php

declare(strict_types=1);

namespace App\DTOs\Plan;

final class UpdatePlanDTO
{
    /**
     * @param  list<PlanPriceDTO>  $prices
     * @param  list<int>  $featureIds
     */
    public function __construct(
        public readonly int $planId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly array $prices,
        public readonly array $featureIds,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(int $planId, array $data): self
    {
        return new self(
            planId: $planId,
            name: (string) $data['name'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
            prices: PlanPriceDTO::listFromArray((array) ($data['prices'] ?? [])),
            featureIds: array_values(array_map(intval(...), (array) ($data['feature_ids'] ?? []))),
        );
    }
}
