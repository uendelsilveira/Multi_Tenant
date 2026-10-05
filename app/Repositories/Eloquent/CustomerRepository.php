<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Customer\CreateCustomerDTO;
use App\DTOs\Customer\UpdateCustomerDTO;
use App\Enums\TenantUserType;
use App\Models\Customer;
use App\Models\TenantUser;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class CustomerRepository implements CustomerRepositoryInterface
{
    public function create(CreateCustomerDTO $dto, int $roleId, int $responsibleUserId): Customer
    {
        return (new TenantUser)->getConnection()->transaction(function () use ($dto, $roleId, $responsibleUserId): Customer {
            // A senha gravada é aleatória e já nasce vencida; o acesso vem com a senha provisória.
            $person = TenantUser::query()->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Str::password(40),
                'role_id' => $roleId,
                'is_active' => true,
                'must_change_password' => true,
                'password_expires_at' => now(),
            ]);

            $customer = Customer::query()->findOrFail($person->id);

            $customer->profile()->create([
                'phone' => $dto->phone,
                'document' => $dto->document,
                'notes' => $dto->notes,
            ]);

            $customer->responsibles()->attach($responsibleUserId, ['created_at' => now()]);

            return $customer->load(['profile', 'responsibles']);
        });
    }

    public function update(Customer $customer, UpdateCustomerDTO $dto): Customer
    {
        return $customer->getConnection()->transaction(function () use ($customer, $dto): Customer {
            $customer->update([
                'name' => $dto->name,
                'email' => $dto->email,
            ]);

            $customer->profile()->updateOrCreate([], [
                'phone' => $dto->phone,
                'document' => $dto->document,
                'notes' => $dto->notes,
            ]);

            return $customer->load(['profile', 'responsibles']);
        });
    }

    public function find(int $id): ?Customer
    {
        return Customer::query()->with(['profile', 'responsibles'])->find($id);
    }

    public function setActive(Customer $customer, bool $active): void
    {
        $customer->forceFill(['is_active' => $active])->save();
    }

    public function isLinkedTo(int $customerId, int $userId): bool
    {
        return Customer::query()
            ->whereKey($customerId)
            ->whereHas('responsibles', fn (Builder $query): Builder => $query->whereKey($userId))
            ->exists();
    }

    public function syncResponsibles(Customer $customer, array $userIds): void
    {
        $customer->responsibles()->syncWithPivotValues($userIds, ['created_at' => now()]);
        $customer->load('responsibles');
    }

    public function countActiveUsers(array $userIds): int
    {
        return $this->activeUsers()->whereKey($userIds)->count();
    }

    public function responsibleOptions(): array
    {
        $options = [];

        foreach ($this->activeUsers()->orderBy('name')->get(['id', 'name']) as $user) {
            $options[$user->id] = $user->name;
        }

        return $options;
    }

    /** @return Builder<TenantUser> */
    private function activeUsers(): Builder
    {
        return TenantUser::query()
            ->where('is_active', true)
            ->whereHas('role', fn (Builder $role): Builder => $role->where('base_type', TenantUserType::User->value));
    }
}
