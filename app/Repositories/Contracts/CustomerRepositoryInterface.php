<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Customer\CreateCustomerDTO;
use App\DTOs\Customer\UpdateCustomerDTO;
use App\Models\Customer;

/**
 * Opera no banco do tenant: só pode ser usado dentro do contexto de um tenant.
 */
interface CustomerRepositoryInterface
{
    /**
     * Cria a pessoa (sem acesso utilizável ainda), os dados de cliente e o
     * vínculo com quem cadastrou, tudo junto.
     */
    public function create(CreateCustomerDTO $dto, int $roleId, int $responsibleUserId): Customer;

    public function update(Customer $customer, UpdateCustomerDTO $dto): Customer;

    public function find(int $id): ?Customer;

    public function setActive(Customer $customer, bool $active): void;

    public function isLinkedTo(int $customerId, int $userId): bool;

    /** @param list<int> $userIds */
    public function syncResponsibles(Customer $customer, array $userIds): void;

    /**
     * Dos ids indicados, quantos são pessoas ativas de tipo usuário.
     *
     * @param  list<int>  $userIds
     */
    public function countActiveUsers(array $userIds): int;

    /**
     * Pessoas ativas de tipo usuário, id => nome, para escolher responsáveis.
     *
     * @return array<int, string>
     */
    public function responsibleOptions(): array;
}
