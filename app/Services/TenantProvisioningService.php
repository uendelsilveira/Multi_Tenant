<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProvisionalPasswordNotifierInterface;
use App\Contracts\TenantEnvironmentInterface;
use App\Enums\ProvisioningStatus;
use App\Exceptions\Tenant\ProvisionalPasswordException;
use App\Exceptions\Tenant\TenantNotFoundException;
use App\Exceptions\Tenant\TenantProvisioningException;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class TenantProvisioningService
{
    /** Validade da senha provisória, em horas (RN28). */
    public const PROVISIONAL_PASSWORD_HOURS = 24;

    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantUserRepositoryInterface $users,
        private readonly TenantEnvironmentInterface $environment,
        private readonly ProvisionalPasswordNotifierInterface $notifier,
    ) {}

    /**
     * Prepara o ambiente do tenant. Cada etapa confere se já foi feita, então
     * uma nova tentativa continua de onde a anterior parou (RN29).
     */
    public function provision(string $tenantId): Tenant
    {
        $tenant = $this->findOrFail($tenantId);

        $name = $tenant->contact_name;
        $email = $tenant->contact_email;

        if ($name === null || $email === null) {
            throw TenantProvisioningException::missingAdminContact($tenant->id);
        }

        $this->tenants->updateProvisioning($tenant, ProvisioningStatus::Provisioning);

        $this->environment->ensureDatabaseExists($tenant);
        $this->environment->migrate($tenant);

        $this->environment->run($tenant, function () use ($tenant, $name, $email): void {
            if ($this->users->findInitialAdmin() !== null) {
                return;
            }

            $password = $this->generatePassword();
            $expiresAt = $this->expiration();

            $admin = $this->users->createInitialAdmin($name, $email, $password, $expiresAt);

            $this->notifier->send($admin, $tenant, $password, $expiresAt);
        });

        $this->tenants->updateProvisioning($tenant, ProvisioningStatus::Ready);

        return $tenant;
    }

    public function markFailed(string $tenantId, string $error): void
    {
        $tenant = $this->tenants->find($tenantId, withTrashed: true);

        if ($tenant === null || $tenant->provisioning_status === ProvisioningStatus::Ready) {
            return;
        }

        $this->tenants->updateProvisioning($tenant, ProvisioningStatus::Failed, Str::limit($error, 1000));
    }

    /** Qualquer tenant que não esteja pronto pode ser provisionado de novo. */
    public function assertCanRetry(string $tenantId): Tenant
    {
        $tenant = $this->findOrFail($tenantId);

        if ($tenant->provisioning_status === ProvisioningStatus::Ready) {
            throw TenantProvisioningException::alreadyProvisioned($tenant->id);
        }

        return $tenant;
    }

    /** O reenvio só existe enquanto o admin nunca entrou (RN28). */
    public function assertCanResendPassword(string $tenantId): Tenant
    {
        $tenant = $this->findOrFail($tenantId);

        $this->environment->run($tenant, fn (): TenantUser => $this->pendingAdmin($tenant));

        return $tenant;
    }

    public function resendProvisionalPassword(string $tenantId): void
    {
        $tenant = $this->findOrFail($tenantId);

        $this->environment->run($tenant, function () use ($tenant): void {
            $admin = $this->pendingAdmin($tenant);

            $password = $this->generatePassword();
            $expiresAt = $this->expiration();

            $this->users->setProvisionalPassword($admin, $password, $expiresAt);

            $this->notifier->send($admin, $tenant, $password, $expiresAt);
        });
    }

    /** Precisa ser chamado dentro do contexto do tenant. */
    private function pendingAdmin(Tenant $tenant): TenantUser
    {
        if ($tenant->provisioning_status !== ProvisioningStatus::Ready) {
            throw ProvisionalPasswordException::tenantNotReady($tenant->id);
        }

        $admin = $this->users->findInitialAdmin() ?? throw ProvisionalPasswordException::adminNotFound($tenant->id);

        if (! $admin->must_change_password) {
            throw ProvisionalPasswordException::alreadyUsed();
        }

        return $admin;
    }

    private function findOrFail(string $tenantId): Tenant
    {
        return $this->tenants->find($tenantId) ?? throw TenantNotFoundException::withId($tenantId);
    }

    private function generatePassword(): string
    {
        return Str::password(16, symbols: false);
    }

    private function expiration(): Carbon
    {
        return Carbon::now()->addHours(self::PROVISIONAL_PASSWORD_HOURS);
    }
}
