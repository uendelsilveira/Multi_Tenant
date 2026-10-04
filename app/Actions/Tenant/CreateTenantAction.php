<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\BaseAction;
use App\DTOs\Tenant\CreateTenantDTO;
use App\Events\Tenant\TenantRegistered;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\Log;

final class CreateTenantAction extends BaseAction
{
    public function __construct(
        private readonly TenantService $service,
    ) {}

    public function execute(CreateTenantDTO $dto): Tenant
    {
        $tenant = $this->service->create($dto);

        event(new TenantRegistered($tenant->id));

        Log::info('tenant.registered', ['tenant_id' => $tenant->id, 'plan_id' => $tenant->plan_id]);

        return $tenant;
    }
}
