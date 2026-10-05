<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\BaseAction;
use App\Models\TenantUser;
use App\Services\TenantUserService;
use Illuminate\Support\Facades\Log;
use SensitiveParameter;

final class ChangeProvisionalPasswordAction extends BaseAction
{
    public function __construct(
        private readonly TenantUserService $service,
    ) {}

    public function execute(TenantUser $user, #[SensitiveParameter] string $newPassword): void
    {
        $this->service->changeProvisionalPassword($user, $newPassword);

        Log::info('tenant_user.provisional_password_changed', ['tenant_user_id' => $user->id]);
    }
}
