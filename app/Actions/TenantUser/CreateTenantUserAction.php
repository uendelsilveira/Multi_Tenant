<?php

declare(strict_types=1);

namespace App\Actions\TenantUser;

use App\Actions\BaseAction;
use App\DTOs\TenantUser\CreateTenantUserDTO;
use App\Events\TenantUser\TenantUserAccessRequested;
use App\Models\TenantUser;
use App\Services\TenantUserService;
use Illuminate\Support\Facades\Log;

final class CreateTenantUserAction extends BaseAction
{
    public function __construct(
        private readonly TenantUserService $service,
    ) {}

    public function execute(CreateTenantUserDTO $dto): TenantUser
    {
        $user = $this->service->create($dto);

        event(new TenantUserAccessRequested($user->id));

        Log::info('tenant_user.created', ['tenant_user_id' => $user->id, 'role_id' => $user->role_id]);

        return $user;
    }
}
