<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\TenantUserType;
use App\Models\Customer;
use App\Models\TenantUser;
use App\Services\CustomerService;
use App\Services\PermissionCatalog;
use App\Services\TenantAccessService;

/**
 * Usuário alcança os clientes dele; quem gerencia todos os clientes alcança
 * qualquer um, mexe nos vínculos e desativa. Só usuário cadastra.
 */
final class CustomerPolicy
{
    public function __construct(
        private readonly TenantAccessService $access,
        private readonly CustomerService $customers,
    ) {}

    public function viewAny(TenantUser $actor): bool
    {
        return $this->managesAll($actor) || $this->access->allows($actor, PermissionCatalog::CUSTOMERS_MANAGE_OWN);
    }

    public function view(TenantUser $actor, Customer $customer): bool
    {
        return $this->customers->canAccess($actor, $customer->id);
    }

    public function create(TenantUser $actor): bool
    {
        return $actor->type === TenantUserType::User
            && $this->access->allows($actor, PermissionCatalog::CUSTOMERS_MANAGE_OWN);
    }

    public function update(TenantUser $actor, Customer $customer): bool
    {
        return $this->customers->canAccess($actor, $customer->id);
    }

    public function resendProvisionalPassword(TenantUser $actor, Customer $customer): bool
    {
        return $this->customers->canAccess($actor, $customer->id);
    }

    public function manageResponsibles(TenantUser $actor, Customer $customer): bool
    {
        return $this->managesAll($actor);
    }

    public function deactivate(TenantUser $actor, Customer $customer): bool
    {
        return $this->managesAll($actor);
    }

    public function activate(TenantUser $actor, Customer $customer): bool
    {
        return $this->managesAll($actor);
    }

    /** Cliente não é excluído, só desativado. */
    public function delete(TenantUser $actor, Customer $customer): bool
    {
        return false;
    }

    private function managesAll(TenantUser $actor): bool
    {
        return $this->access->allows($actor, PermissionCatalog::CUSTOMERS_MANAGE_ALL);
    }
}
