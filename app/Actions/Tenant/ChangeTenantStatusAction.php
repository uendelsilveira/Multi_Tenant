<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\BaseAction;
use App\DTOs\Tenant\ChangeTenantStatusDTO;
use App\Enums\TenantStatusSource;
use App\Events\Tenant\TenantStatusChanged;
use App\Models\Tenant;
use App\Services\TenantStatusService;
use Illuminate\Support\Facades\Log;

final class ChangeTenantStatusAction extends BaseAction
{
    public function __construct(
        private readonly TenantStatusService $service,
    ) {}

    public function execute(ChangeTenantStatusDTO $dto): Tenant
    {
        $tenant = $this->service->changeManually($dto);

        event(new TenantStatusChanged($tenant->id, $tenant->status, TenantStatusSource::Manual));

        Log::info('tenant.status_changed', [
            'tenant_id' => $tenant->id,
            'status' => $tenant->status->value,
            'source' => TenantStatusSource::Manual->value,
            'central_user_id' => $dto->centralUserId,
            'locked_until' => $dto->lockedUntil?->toIso8601String(),
        ]);

        return $tenant;
    }
}
