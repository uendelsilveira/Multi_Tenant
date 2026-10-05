<?php

declare(strict_types=1);

namespace App\Actions\Feature;

use App\Actions\BaseAction;
use App\Events\Feature\FeatureToggled;
use App\Models\Tenant;
use App\Services\TenantFeatureService;
use Illuminate\Support\Facades\Log;

final class ToggleFeatureAction extends BaseAction
{
    public function __construct(
        private readonly TenantFeatureService $service,
    ) {}

    public function execute(Tenant $tenant, string $featureKey, bool $enabled): void
    {
        $this->service->toggle($tenant, $featureKey, $enabled);

        event(new FeatureToggled($tenant->id, $featureKey, $enabled));

        Log::info('feature.toggled', ['tenant_id' => $tenant->id, 'feature' => $featureKey, 'enabled' => $enabled]);
    }
}
