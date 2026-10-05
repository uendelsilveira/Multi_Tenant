<?php

declare(strict_types=1);

namespace App\Actions\Role;

use App\Actions\BaseAction;
use App\DTOs\Role\CreateRoleDTO;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Support\Facades\Log;

final class CreateRoleAction extends BaseAction
{
    public function __construct(
        private readonly RoleService $service,
    ) {}

    public function execute(CreateRoleDTO $dto): Role
    {
        $role = $this->service->create($dto);

        Log::info('role.created', ['role_id' => $role->id]);

        return $role;
    }
}
