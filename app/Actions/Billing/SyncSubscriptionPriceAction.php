<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Actions\BaseAction;
use App\Services\BillingService;
use Illuminate\Support\Facades\Log;

final class SyncSubscriptionPriceAction extends BaseAction
{
    public function __construct(
        private readonly BillingService $service,
    ) {}

    public function execute(string $tenantId): void
    {
        $synced = $this->service->syncSubscriptionPrice($tenantId);

        Log::info('subscription.price_synced', ['tenant_id' => $tenantId, 'synced' => $synced]);
    }
}
