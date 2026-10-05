<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Tenant;
use App\Models\TenantUser;
use Carbon\CarbonInterface;

interface ProvisionalPasswordNotifierInterface
{
    /** Entrega a senha provisória ao admin. A senha em texto não é guardada em lugar nenhum. */
    public function send(TenantUser $admin, Tenant $tenant, string $plainPassword, CarbonInterface $expiresAt): void;
}
