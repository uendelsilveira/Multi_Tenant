<?php

declare(strict_types=1);

namespace App\Actions\TenantUser;

use App\Actions\BaseAction;
use App\Models\Tenant;
use App\Services\TenantUserService;
use Illuminate\Support\Facades\Log;

final class IssueTenantUserProvisionalPasswordAction extends BaseAction
{
    public function __construct(
        private readonly TenantUserService $service,
    ) {}

    public function execute(int $userId, Tenant $tenant): void
    {
        $this->service->issueProvisionalPassword($userId, $tenant);

        Log::info('tenant_user.provisional_password_issued', ['tenant_id' => $tenant->id, 'tenant_user_id' => $userId]);
    }
}
