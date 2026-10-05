<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\TenantUser;
use Carbon\CarbonInterface;
use SensitiveParameter;

/**
 * Opera no banco do tenant: só pode ser usado dentro do contexto de um tenant.
 */
interface TenantUserRepositoryInterface
{
    /** O admin mais antigo do tenant. */
    public function findInitialAdmin(): ?TenantUser;

    public function createInitialAdmin(
        string $name,
        string $email,
        #[SensitiveParameter] string $plainPassword,
        CarbonInterface $expiresAt,
    ): TenantUser;

    public function setProvisionalPassword(
        TenantUser $user,
        #[SensitiveParameter] string $plainPassword,
        CarbonInterface $expiresAt,
    ): void;

    /** Grava a senha definitiva e encerra a pendência de troca. */
    public function changePassword(TenantUser $user, #[SensitiveParameter] string $plainPassword): void;
}
