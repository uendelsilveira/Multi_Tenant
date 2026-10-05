<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Actions\BaseAction;
use App\Enums\TenantStatus;
use App\Enums\TenantStatusSource;
use App\Events\Tenant\TenantStatusChanged;
use App\Services\BillingService;
use Illuminate\Support\Facades\Log;

final class EnforceGracePeriodAction extends BaseAction
{
    public function __construct(
        private readonly BillingService $service,
    ) {}

    /** @return list<string> ids dos tenants suspensos agora */
    public function execute(): array
    {
        $suspended = $this->service->enforceGracePeriod();

        foreach ($suspended as $tenantId) {
            event(new TenantStatusChanged($tenantId, TenantStatus::Suspended, TenantStatusSource::Gateway));
        }

        Log::info('billing.grace_enforced', ['suspended' => $suspended]);

        return $suspended;
    }
}
