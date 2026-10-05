<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Actions\BaseAction;
use App\Events\Billing\TenantSubscriptionRetryRequested;
use App\Services\BillingService;
use Illuminate\Support\Facades\Log;

final class RetryTenantSubscriptionAction extends BaseAction
{
    public function __construct(
        private readonly BillingService $service,
    ) {}

    public function execute(string $tenantId): void
    {
        $this->service->assertCanRetry($tenantId);

        event(new TenantSubscriptionRetryRequested($tenantId));

        Log::info('subscription.retry_requested', ['tenant_id' => $tenantId]);
    }
}
