<?php

declare(strict_types=1);

namespace App\Actions\Role;

use App\Actions\BaseAction;
use App\Services\RoleService;
use Illuminate\Support\Facades\Log;

final class DeleteRoleAction extends BaseAction
{
    public function __construct(
        private readonly RoleService $service,
    ) {}

    public function execute(int $roleId): void
    {
        $this->service->delete($roleId);

        Log::info('role.deleted', ['role_id' => $roleId]);
    }
}
