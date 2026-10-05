<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Actions\BaseAction;
use App\Services\BillingService;
use Illuminate\Support\Facades\Log;

final class StartTenantSubscriptionAction extends BaseAction
{
    public function __construct(
        private readonly BillingService $service,
    ) {}

    public function execute(string $tenantId): void
    {
        $created = $this->service->startSubscription($tenantId);

        Log::info('subscription.started', ['tenant_id' => $tenantId, 'created_now' => $created]);
    }
}
