<?php

declare(strict_types=1);

namespace App\Events\Feature;

use Illuminate\Foundation\Events\Dispatchable;

final class FeatureToggled
{
    use Dispatchable;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $featureKey,
        public readonly bool $enabled,
    ) {}
}
