<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Customer\CreateCustomerDTO;
use App\DTOs\Customer\UpdateCustomerDTO;
use App\Enums\TenantUserType;
use App\Exceptions\Customer\CustomerNotFoundException;
use App\Exceptions\Customer\CustomerRuleException;
use App\Models\Customer;
use App\Models\Role;
use App\Models\TenantUser;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use App\Services\CustomerService;
use App\Services\DocumentValidator;
use App\Services\PermissionCatalog;
use App\Services\TenantAccessService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class CustomerServiceTest extends TestCase
{
    private CustomerRepositoryInterface&MockInterface $customers;

    private TenantUserRepositoryInterface&MockInterface $users;

    private RoleRepositoryInterface&MockInterface $roles;

    private CustomerService $service;

    private TenantUser $admin;

    private TenantUser $user;

    private TenantUser $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customers = Mockery::mock(CustomerRepositoryInterface::class);
        $this->users = Mockery::mock(TenantUserRepositoryInterface::class);
        $this->roles = Mockery::mock(RoleRepositoryInterface::class);

        $this->users->shouldReceive('emailExists')->andReturn(false)->byDefault();
        $this->roles->shouldReceive('systemRole')->with(TenantUserType::Customer)->andReturn($this->role(3, TenantUserType::Customer))->byDefault();

        $catalog = new PermissionCatalog((array) config('permissions.catalog'));

        $this->service = new CustomerService(
            $this->customers,
            $this->users,
            $this->roles,
            new TenantAccessService($catalog, $this->roles, $this->users),
            new DocumentValidator,
        );

        $this->admin = $this->person(1, $this->role(1, TenantUserType::Admin));
        $this->user = $this->person(2, $this->role(2, TenantUserType::User));
        $this->otherUser = $this->person(5, $this->role(2, TenantUserType::User));
    }

    public function test_a_user_registers_a_customer_who_is_born_linked_to_them(): void
    {
        $dto = new CreateCustomerDTO('Cliente Um', 'um@cliente.test', '51999990000', '52998224725', null);
        $customer = new Customer;

        $this->customers->shouldReceive('create')->once()->with($dto, 3, 2)->andReturn($customer);

        $this->assertSame($customer, $this->service->create($dto, $this->user));
    }

    public function test_an_admin_does_not_register_customers(): void
    {
        $this->customers->shouldNotReceive('create');

        $this->expectExceptionObject(CustomerRuleException::onlyUsersRegister());

        $this->service->create(new CreateCustomerDTO('Cliente Um', 'um@cliente.test', null, null, null), $this->admin);
    }

    public function test_the_email_is_unique_across_the_whole_tenant(): void
    {
        $this->users->shouldReceive('emailExists')->with('um@cliente.test', null)->andReturn(true);
        $this->customers->shouldNotReceive('create');

        $this->expectExceptionObject(CustomerRuleException::emailTaken('um@cliente.test'));

        $this->service->create(new CreateCustomerDTO('Cliente Um', 'um@cliente.test', null, null, null), $this->user);
    }

    public function test_the_document_is_optional_but_must_be_valid_when_given(): void
    {
        $this->customers->shouldReceive('create')->times(3)->andReturn(new Customer);

        $this->service->create(new CreateCustomerDTO('Sem documento', 'a@cliente.test', null, null, null), $this->user);
        $this->service->create(new CreateCustomerDTO('Com CPF', 'b@cliente.test', null, '52998224725', null), $this->user);
        $this->service->create(new CreateCustomerDTO('Com CNPJ', 'c@cliente.test', null, '11222333000181', null), $this->user);

        $this->expectExceptionObject(CustomerRuleException::invalidDocument('52998224726'));

        $this->service->create(new CreateCustomerDTO('CPF errado', 'd@cliente.test', null, '52998224726', null), $this->user);
    }

    public function test_a_user_reaches_only_customers_linked_to_them_and_an_admin_reaches_all(): void
    {
        $this->customers->shouldReceive('isLinkedTo')->with(10, 2)->andReturn(true);
        $this->customers->shouldReceive('isLinkedTo')->with(10, 5)->andReturn(false);

        $this->assertTrue($this->service->canAccess($this->user, 10));
        $this->assertFalse($this->service->canAccess($this->otherUser, 10));
        $this->assertTrue($this->service->canAccess($this->admin, 10));
    }

    public function test_updating_a_customer_of_someone_else_is_refused(): void
    {
        $this->customers->shouldReceive('find')->with(10)->andReturn($this->customer(10));
        $this->customers->shouldReceive('isLinkedTo')->with(10, 5)->andReturn(false);
        $this->customers->shouldNotReceive('update');

        $this->expectExceptionObject(CustomerRuleException::notAccessible());

        $this->service->update(new UpdateCustomerDTO(10, 'Outro nome', 'um@cliente.test', null, null, null), $this->otherUser);
    }

    public function test_updating_keeps_the_email_unique_ignoring_the_customer_itself(): void
    {
        $customer = $this->customer(10);
        $dto = new UpdateCustomerDTO(10, 'Cliente Um', 'novo@cliente.test', null, null, null);

        $this->customers->shouldReceive('find')->with(10)->andReturn($customer);
        $this->customers->shouldReceive('isLinkedTo')->with(10, 2)->andReturn(true);
        $this->users->shouldReceive('emailExists')->once()->with('novo@cliente.test', 10)->andReturn(false);
        $this->customers->shouldReceive('update')->once()->with($customer, $dto)->andReturn($customer);

        $this->assertSame($customer, $this->service->update($dto, $this->user));
    }

    public function test_a_customer_always_keeps_at_least_one_responsible(): void
    {
        $this->customers->shouldReceive('find')->with(10)->andReturn($this->customer(10));
        $this->customers->shouldNotReceive('syncResponsibles');

        $this->expectExceptionObject(CustomerRuleException::needsResponsible());

        $this->service->syncResponsibles(10, []);
    }

    public function test_only_active_users_can_be_responsible(): void
    {
        $this->customers->shouldReceive('find')->with(10)->andReturn($this->customer(10));
        $this->customers->shouldReceive('countActiveUsers')->with([2, 1])->andReturn(1);
        $this->customers->shouldNotReceive('syncResponsibles');

        $this->expectExceptionObject(CustomerRuleException::invalidResponsible());

        // O id 1 é um admin: não conta como usuário.
        $this->service->syncResponsibles(10, [2, 1]);
    }

    public function test_responsibles_are_replaced_by_the_given_users_without_repetition(): void
    {
        $customer = $this->customer(10);

        $this->customers->shouldReceive('find')->with(10)->andReturn($customer);
        $this->customers->shouldReceive('countActiveUsers')->with([2, 5])->andReturn(2);
        $this->customers->shouldReceive('syncResponsibles')->once()->with($customer, [2, 5]);

        $this->service->syncResponsibles(10, [2, 5, 2]);
    }

    public function test_it_fails_for_a_customer_that_does_not_exist(): void
    {
        $this->customers->shouldReceive('find')->with(99)->andReturn(null);

        $this->expectException(CustomerNotFoundException::class);

        $this->service->setActive(99, false);
    }

    private function role(int $id, TenantUserType $type): Role
    {
        $role = new Role(['name' => $type->label(), 'base_type' => $type, 'is_system' => true]);
        $role->id = $id;

        return $role;
    }

    private function person(int $id, Role $role): TenantUser
    {
        $person = new TenantUser(['name' => 'Pessoa', 'email' => "p{$id}@acme.test"]);
        $person->id = $id;

        return $person->setRelation('role', $role);
    }

    private function customer(int $id): Customer
    {
        $customer = new Customer(['name' => 'Cliente Um', 'email' => 'um@cliente.test']);
        $customer->id = $id;

        return $customer;
    }
}
