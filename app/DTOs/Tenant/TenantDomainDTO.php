<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

use App\Enums\DomainPanel;

final class TenantDomainDTO
{
    public function __construct(
        public readonly string $host,
        public readonly DomainPanel $panel,
    ) {}

    /**
     * @param  array<int|string, mixed>  $domains
     * @return list<self>
     */
    public static function listFromArray(array $domains): array
    {
        $list = [];

        foreach ($domains as $domain) {
            if (! is_array($domain)) {
                continue;
            }

            $panel = $domain['panel'] ?? DomainPanel::Admin->value;

            $list[] = new self(
                host: (string) ($domain['domain'] ?? ''),
                panel: $panel instanceof DomainPanel ? $panel : DomainPanel::from((string) $panel),
            );
        }

        return $list;
    }
}
