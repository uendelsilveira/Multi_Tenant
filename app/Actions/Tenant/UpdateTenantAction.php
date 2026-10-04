<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\BaseAction;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\Log;

final class UpdateTenantAction extends BaseAction
{
    public function __construct(
        private readonly TenantService $service,
    ) {}

    public function execute(UpdateTenantDTO $dto): Tenant
    {
        $tenant = $this->service->update($dto);

        Log::info('tenant.updated', ['tenant_id' => $tenant->id]);

        return $tenant;
    }
}
