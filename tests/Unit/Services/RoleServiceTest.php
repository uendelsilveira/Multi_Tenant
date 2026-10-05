<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Role\CreateRoleDTO;
use App\DTOs\Role\UpdateRoleDTO;
use App\Enums\TenantUserType;
use App\Exceptions\Role\RoleNotFoundException;
use App\Exceptions\Role\RoleRuleException;
use App\Exceptions\TenantUser\TenantUserRuleException;
use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use App\Services\PermissionCatalog;
use App\Services\RoleService;
use App\Services\TenantAccessService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class RoleServiceTest extends TestCase
{
    private RoleRepositoryInterface&MockInterface $roles;

    private TenantUserRepositoryInterface&MockInterface $users;

    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roles = Mockery::mock(RoleRepositoryInterface::class);
        $this->users = Mockery::mock(TenantUserRepositoryInterface::class);

        $catalog = new PermissionCatalog((array) config('permissions.catalog'));

        $this->service = new RoleService($this->roles, $catalog, new TenantAccessService($catalog, $this->roles, $this->users));
    }

    public function test_it_creates_a_role_keeping_only_permissions_of_its_type(): void
    {
        $dto = new CreateRoleDTO('Atendente', TenantUserType::User, ['people.manage', 'inventada']);
        $role = new Role;

        $this->roles->shouldReceive('nameExists')->with('Atendente')->andReturn(false);
        $this->roles->shouldReceive('create')->once()->with($dto, [])->andReturn($role);

        $this->assertSame($role, $this->service->create($dto));
    }

    public function test_it_rejects_a_duplicated_name(): void
    {
        $this->roles->shouldReceive('nameExists')->andReturn(true);
        $this->roles->shouldNotReceive('create');

        $this->expectExceptionObject(RoleRuleException::nameTaken('Admin'));

        $this->service->create(new CreateRoleDTO('Admin', TenantUserType::Admin, []));
    }

    public function test_a_system_role_is_never_changed_or_deleted(): void
    {
        $this->roles->shouldReceive('find')->with(1)->andReturn($this->role(system: true));
        $this->roles->shouldNotReceive('update');
        $this->roles->shouldNotReceive('delete');

        try {
            $this->service->update(new UpdateRoleDTO(1, 'Outro nome', TenantUserType::User, []));
            $this->fail('Perfil de sistema não pode ser alterado.');
        } catch (RoleRuleException $e) {
            $this->assertEquals(RoleRuleException::systemRole('Perfil'), $e);
        }

        $this->expectExceptionObject(RoleRuleException::systemRole('Perfil'));
        $this->service->delete(1);
    }

    public function test_changing_the_base_type_drops_permissions_that_do_not_apply_to_the_new_type(): void
    {
        $role = $this->role(permissions: ['roles.manage']);
        $dto = new UpdateRoleDTO(1, 'Perfil', TenantUserType::User, ['roles.manage']);

        $this->roles->shouldReceive('find')->andReturn($role);
        $this->roles->shouldReceive('nameExists')->andReturn(false);
        $this->roles->shouldReceive('update')->once()->with($role, $dto, [])->andReturn($role);

        $this->service->update($dto);
    }

    public function test_a_change_that_would_leave_nobody_managing_people_is_refused(): void
    {
        $role = $this->role(permissions: ['people.manage'], id: 5);

        $this->roles->shouldReceive('find')->andReturn($role);
        $this->roles->shouldReceive('nameExists')->andReturn(false);
        $this->roles->shouldReceive('all')->andReturn(new Collection([$role, $this->role(system: true, id: 1)]));
        $this->users->shouldReceive('countActiveInRoles')->with([1], null)->andReturn(0);
        $this->roles->shouldNotReceive('update');

        $this->expectExceptionObject(TenantUserRuleException::lastPeopleManager());

        // Trocar o tipo para usuário faz o perfil deixar de gerenciar pessoas.
        $this->service->update(new UpdateRoleDTO(5, 'Perfil', TenantUserType::User, ['people.manage']));
    }

    public function test_the_same_change_goes_through_when_someone_else_still_manages_people(): void
    {
        $role = $this->role(permissions: ['people.manage'], id: 5);
        $dto = new UpdateRoleDTO(5, 'Perfil', TenantUserType::User, []);

        $this->roles->shouldReceive('find')->andReturn($role);
        $this->roles->shouldReceive('nameExists')->andReturn(false);
        $this->roles->shouldReceive('all')->andReturn(new Collection([$role, $this->role(system: true, id: 1)]));
        $this->users->shouldReceive('countActiveInRoles')->with([1], null)->andReturn(1);
        $this->roles->shouldReceive('update')->once()->with($role, $dto, [])->andReturn($role);

        $this->service->update($dto);
    }

    public function test_a_role_with_people_is_not_deleted(): void
    {
        $this->roles->shouldReceive('find')->andReturn($this->role());
        $this->roles->shouldReceive('hasUsers')->with(1)->andReturn(true);
        $this->roles->shouldNotReceive('delete');

        $this->expectExceptionObject(RoleRuleException::inUse('Perfil'));

        $this->service->delete(1);
    }

    public function test_an_empty_custom_role_is_deleted(): void
    {
        $role = $this->role();

        $this->roles->shouldReceive('find')->andReturn($role);
        $this->roles->shouldReceive('hasUsers')->andReturn(false);
        $this->roles->shouldReceive('delete')->once()->with($role);

        $this->service->delete(1);
    }

    public function test_it_fails_for_a_role_that_does_not_exist(): void
    {
        $this->roles->shouldReceive('find')->with(99)->andReturn(null);

        $this->expectException(RoleNotFoundException::class);

        $this->service->delete(99);
    }

    /** @param list<string> $permissions */
    private function role(bool $system = false, array $permissions = [], int $id = 1): Role
    {
        $role = new Role(['name' => 'Perfil', 'base_type' => TenantUserType::Admin, 'is_system' => $system, 'permissions' => $permissions]);
        $role->id = $id;

        return $role;
    }
}
