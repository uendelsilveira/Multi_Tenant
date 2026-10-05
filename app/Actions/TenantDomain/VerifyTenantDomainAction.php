<?php

declare(strict_types=1);

namespace App\Actions\TenantDomain;

use App\Actions\BaseAction;
use App\Events\TenantDomain\TenantDomainVerified;
use App\Services\TenantDomainService;
use Illuminate\Support\Facades\Log;

final class VerifyTenantDomainAction extends BaseAction
{
    public function __construct(
        private readonly TenantDomainService $service,
    ) {}

    public function execute(int $domainId, int $centralUserId): void
    {
        $domain = $this->service->verify($domainId, $centralUserId);

        event(new TenantDomainVerified($domain->id, $domain->tenant_id));

        Log::info('tenant_domain.verified', [
            'domain_id' => $domain->id,
            'tenant_id' => $domain->tenant_id,
            'verified_by' => $centralUserId,
        ]);
    }
}
