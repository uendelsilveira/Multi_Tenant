<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Tenant\ProvisionalPasswordException;
use App\Models\TenantUser;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use SensitiveParameter;

final class TenantUserService
{
    public function __construct(
        private readonly TenantUserRepositoryInterface $users,
    ) {}

    public function mustChangePassword(TenantUser $user): bool
    {
        return $user->must_change_password;
    }

    public function provisionalPasswordExpired(TenantUser $user): bool
    {
        return $user->must_change_password
            && $user->password_expires_at !== null
            && $user->password_expires_at->isPast();
    }

    public function changeProvisionalPassword(TenantUser $user, #[SensitiveParameter] string $newPassword): void
    {
        if (! $this->mustChangePassword($user)) {
            throw ProvisionalPasswordException::notPending();
        }

        if ($this->provisionalPasswordExpired($user)) {
            throw ProvisionalPasswordException::expired();
        }

        $this->users->changePassword($user, $newPassword);
    }
}
