<?php

declare(strict_types=1);

namespace App\Actions\TenantUser;

use App\Actions\BaseAction;
use App\Events\TenantUser\TenantUserAccessRequested;
use App\Services\TenantUserService;
use Illuminate\Support\Facades\Log;

/**
 * Reenvio pedido pelo admin do tenant: confere a regra na hora e deixa o
 * envio para a fila.
 */
final class RequestTenantUserProvisionalPasswordAction extends BaseAction
{
    public function __construct(
        private readonly TenantUserService $service,
    ) {}

    public function execute(int $userId): void
    {
        $this->service->assertCanIssueProvisionalPassword($userId);

        event(new TenantUserAccessRequested($userId));

        Log::info('tenant_user.provisional_password_requested', ['tenant_user_id' => $userId]);
    }
}
