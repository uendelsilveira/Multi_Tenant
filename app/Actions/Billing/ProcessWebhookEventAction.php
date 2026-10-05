<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Actions\BaseAction;
use App\Enums\TenantStatusSource;
use App\Events\Tenant\TenantStatusChanged;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\BillingService;
use Illuminate\Support\Facades\Log;

final class ProcessWebhookEventAction extends BaseAction
{
    public function __construct(
        private readonly BillingService $service,
        private readonly TenantRepositoryInterface $tenants,
    ) {}

    public function execute(int $webhookEventId): void
    {
        $tenantId = $this->service->handleEvent($webhookEventId);

        Log::info('webhook.processed', ['webhook_event_id' => $webhookEventId, 'status_changed_for' => $tenantId]);

        if ($tenantId === null) {
            return;
        }

        $tenant = $this->tenants->find($tenantId, withTrashed: true);

        if ($tenant !== null) {
            event(new TenantStatusChanged($tenant->id, $tenant->status, TenantStatusSource::Gateway));
        }
    }
}
