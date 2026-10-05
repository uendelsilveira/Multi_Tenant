<?php

declare(strict_types=1);

namespace App\Actions\TenantUser;

use App\Actions\BaseAction;
use App\DTOs\TenantUser\UpdateTenantUserDTO;
use App\Models\TenantUser;
use App\Services\TenantUserService;
use Illuminate\Support\Facades\Log;

final class UpdateTenantUserAction extends BaseAction
{
    public function __construct(
        private readonly TenantUserService $service,
    ) {}

    public function execute(UpdateTenantUserDTO $dto): TenantUser
    {
        $user = $this->service->update($dto);

        Log::info('tenant_user.updated', ['tenant_user_id' => $user->id, 'role_id' => $user->role_id]);

        return $user;
    }
}
