<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Customer\CreateCustomerDTO;
use App\DTOs\Customer\UpdateCustomerDTO;
use App\Enums\PersonType;
use App\Enums\TenantUserType;
use App\Exceptions\Customer\CustomerNotFoundException;
use App\Exceptions\Customer\CustomerRuleException;
use App\Exceptions\Role\RoleNotFoundException;
use App\Models\Customer;
use App\Models\TenantUser;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TenantUserRepositoryInterface;

/**
 * Clientes de um tenant. Tudo aqui roda dentro do contexto do tenant.
 */
final class CustomerService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly TenantUserRepositoryInterface $users,
        private readonly RoleRepositoryInterface $roles,
        private readonly TenantAccessService $access,
        private readonly DocumentValidator $documents,
    ) {}

    /** Só usuário cadastra cliente, e o cliente nasce vinculado a quem cadastrou (RN14). */
    public function create(CreateCustomerDTO $dto, TenantUser $actor): Customer
    {
        if ($actor->type !== TenantUserType::User || ! $this->access->allows($actor, PermissionCatalog::CUSTOMERS_MANAGE_OWN)) {
            throw CustomerRuleException::onlyUsersRegister();
        }

        $this->assertValidData($dto->email, $dto->document, null);

        $role = $this->roles->systemRole(TenantUserType::Customer) ?? throw RoleNotFoundException::withId(0);

        return $this->customers->create($dto, $role->id, $actor->id);
    }

    public function update(UpdateCustomerDTO $dto, TenantUser $actor): Customer
    {
        $customer = $this->findAccessible($dto->customerId, $actor);

        $this->assertValidData($dto->email, $dto->document, $customer->id);

        return $this->customers->update($customer, $dto);
    }

    /** O usuário alcança os clientes vinculados a ele; quem gerencia todos alcança qualquer um (RF17). */
    public function canAccess(TenantUser $actor, int $customerId): bool
    {
        if ($this->access->allows($actor, PermissionCatalog::CUSTOMERS_MANAGE_ALL)) {
            return true;
        }

        return $this->access->allows($actor, PermissionCatalog::CUSTOMERS_MANAGE_OWN)
            && $this->customers->isLinkedTo($customerId, $actor->id);
    }

    public function findAccessible(int $customerId, TenantUser $actor): Customer
    {
        $customer = $this->customers->find($customerId) ?? throw CustomerNotFoundException::withId($customerId);

        if (! $this->canAccess($actor, $customer->id)) {
            throw CustomerRuleException::notAccessible();
        }

        return $customer;
    }

    /**
     * Define quem atende o cliente. Sempre resta ao menos um responsável, e só
     * usuário ativo pode ser responsável.
     *
     * @param  list<int>  $userIds
     */
    public function syncResponsibles(int $customerId, array $userIds): Customer
    {
        $customer = $this->customers->find($customerId) ?? throw CustomerNotFoundException::withId($customerId);

        $userIds = array_values(array_unique($userIds));

        if ($userIds === []) {
            throw CustomerRuleException::needsResponsible();
        }

        if ($this->customers->countActiveUsers($userIds) !== count($userIds)) {
            throw CustomerRuleException::invalidResponsible();
        }

        $this->customers->syncResponsibles($customer, $userIds);

        return $customer;
    }

    public function setActive(int $customerId, bool $active): Customer
    {
        $customer = $this->customers->find($customerId) ?? throw CustomerNotFoundException::withId($customerId);

        $this->customers->setActive($customer, $active);

        return $customer;
    }

    /** @return array<int, string> */
    public function responsibleOptions(): array
    {
        return $this->customers->responsibleOptions();
    }

    /** O e-mail é único entre todas as pessoas do tenant; o documento, quando informado, precisa ser válido. */
    private function assertValidData(string $email, ?string $document, ?int $exceptId): void
    {
        if ($this->users->emailExists($email, $exceptId)) {
            throw CustomerRuleException::emailTaken($email);
        }

        if ($document === null) {
            return;
        }

        $type = strlen($document) === 11 ? PersonType::Individual : PersonType::Company;

        if (! $this->documents->isValid($type, $document)) {
            throw CustomerRuleException::invalidDocument($document);
        }
    }
}
