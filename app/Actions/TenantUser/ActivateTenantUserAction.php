<?php

declare(strict_types=1);

namespace App\Actions\TenantUser;

use App\Actions\BaseAction;
use App\Services\TenantUserService;
use Illuminate\Support\Facades\Log;

final class ActivateTenantUserAction extends BaseAction
{
    public function __construct(
        private readonly TenantUserService $service,
    ) {}

    public function execute(int $userId): void
    {
        $this->service->activate($userId);

        Log::info('tenant_user.activated', ['tenant_user_id' => $userId]);
    }
}
