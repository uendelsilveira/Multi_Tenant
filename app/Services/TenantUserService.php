<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProvisionalPasswordNotifierInterface;
use App\DTOs\TenantUser\CreateTenantUserDTO;
use App\DTOs\TenantUser\UpdateTenantUserDTO;
use App\Enums\TenantUserType;
use App\Exceptions\Role\RoleNotFoundException;
use App\Exceptions\Tenant\ProvisionalPasswordException;
use App\Exceptions\TenantUser\TenantUserNotFoundException;
use App\Exceptions\TenantUser\TenantUserRuleException;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use SensitiveParameter;

/**
 * Pessoas de um tenant. Tudo aqui roda dentro do contexto do tenant.
 */
final class TenantUserService
{
    public function __construct(
        private readonly TenantUserRepositoryInterface $users,
        private readonly RoleRepositoryInterface $roles,
        private readonly TenantAccessService $access,
        private readonly ProvisionalPasswordNotifierInterface $notifier,
    ) {}

    /** A pessoa nasce sem acesso utilizável; a senha provisória é emitida em seguida, em fila. */
    public function create(CreateTenantUserDTO $dto): TenantUser
    {
        if ($this->users->emailExists($dto->email)) {
            throw TenantUserRuleException::emailTaken($dto->email);
        }

        $this->assignableRole($dto->roleId);

        return $this->users->createAwaitingAccess($dto);
    }

    public function update(UpdateTenantUserDTO $dto): TenantUser
    {
        $user = $this->findOrFail($dto->userId);

        if ($this->users->emailExists($dto->email, $user->id)) {
            throw TenantUserRuleException::emailTaken($dto->email);
        }

        $newRole = $this->assignableRole($dto->roleId);

        $losesPeopleManagement = $this->access->allows($user, PermissionCatalog::PEOPLE_MANAGE)
            && ! in_array(PermissionCatalog::PEOPLE_MANAGE, $this->access->permissionsOf($newRole), true);

        if ($losesPeopleManagement) {
            $this->access->assertPeopleManagerRemains(exceptUserId: $user->id);
        }

        return $this->users->update($user, $dto);
    }

    public function deactivate(int $userId, int $actorId): TenantUser
    {
        if ($userId === $actorId) {
            throw TenantUserRuleException::cannotDeactivateSelf();
        }

        $user = $this->findOrFail($userId);

        if ($this->access->allows($user, PermissionCatalog::PEOPLE_MANAGE)) {
            $this->access->assertPeopleManagerRemains(exceptUserId: $user->id);
        }

        $this->users->setActive($user, false);

        return $user;
    }

    public function activate(int $userId): TenantUser
    {
        $user = $this->findOrFail($userId);

        $this->users->setActive($user, true);

        return $user;
    }

    /** A senha provisória só é emitida para pessoa ativa que ainda não fez o primeiro acesso (RN28). */
    public function assertCanIssueProvisionalPassword(int $userId): TenantUser
    {
        $user = $this->findOrFail($userId);

        if (! $user->is_active) {
            throw TenantUserRuleException::inactive();
        }

        if (! $user->must_change_password) {
            throw ProvisionalPasswordException::alreadyUsed();
        }

        return $user;
    }

    public function issueProvisionalPassword(int $userId, Tenant $tenant): void
    {
        $user = $this->assertCanIssueProvisionalPassword($userId);

        $password = Str::password(16, symbols: false);
        $expiresAt = Carbon::now()->addHours(TenantProvisioningService::PROVISIONAL_PASSWORD_HOURS);

        $this->users->setProvisionalPassword($user, $password, $expiresAt);

        $this->notifier->send($user, $tenant, $password, $expiresAt);
    }

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

    private function findOrFail(int $userId): TenantUser
    {
        return $this->users->find($userId) ?? throw TenantUserNotFoundException::withId($userId);
    }

    /** Nesta tela só se atribuem perfis de tipo admin ou usuário; clientes têm cadastro próprio (RN14). */
    private function assignableRole(int $roleId): Role
    {
        $role = $this->roles->find($roleId) ?? throw RoleNotFoundException::withId($roleId);

        if (! in_array($role->base_type, [TenantUserType::Admin, TenantUserType::User], true)) {
            throw TenantUserRuleException::customerRole();
        }

        return $role;
    }
}
