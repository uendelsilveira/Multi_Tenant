<?php

declare(strict_types=1);

namespace App\Actions\Role;

use App\Actions\BaseAction;
use App\DTOs\Role\UpdateRoleDTO;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Support\Facades\Log;

final class UpdateRoleAction extends BaseAction
{
    public function __construct(
        private readonly RoleService $service,
    ) {}

    public function execute(UpdateRoleDTO $dto): Role
    {
        $role = $this->service->update($dto);

        Log::info('role.updated', ['role_id' => $role->id]);

        return $role;
    }
}
