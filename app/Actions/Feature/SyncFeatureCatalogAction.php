<?php

declare(strict_types=1);

namespace App\Actions\Feature;

use App\Actions\BaseAction;
use App\Services\FeatureService;
use Illuminate\Support\Facades\Log;

final class SyncFeatureCatalogAction extends BaseAction
{
    public function __construct(
        private readonly FeatureService $service,
    ) {}

    /** @param array<int|string, mixed> $catalog */
    public function execute(array $catalog): int
    {
        $count = $this->service->syncCatalog($catalog);

        Log::info('feature.catalog_synced', ['count' => $count]);

        return $count;
    }
}
