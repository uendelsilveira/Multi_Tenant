<?php

declare(strict_types=1);

namespace App\Actions\TenantUser;

use App\Actions\BaseAction;
use App\Services\TenantUserService;
use Illuminate\Support\Facades\Log;

final class DeactivateTenantUserAction extends BaseAction
{
    public function __construct(
        private readonly TenantUserService $service,
    ) {}

    public function execute(int $userId, int $actorId): void
    {
        $this->service->deactivate($userId, $actorId);

        Log::info('tenant_user.deactivated', ['tenant_user_id' => $userId, 'actor_id' => $actorId]);
    }
}
