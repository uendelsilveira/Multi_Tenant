<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\TenantUserType;
use App\Exceptions\TenantUser\TenantUserRuleException;
use App\Models\Role;
use App\Models\TenantUser;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use App\Services\PermissionCatalog;
use App\Services\TenantAccessService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class TenantAccessServiceTest extends TestCase
{
    private RoleRepositoryInterface&MockInterface $roles;

    private TenantUserRepositoryInterface&MockInterface $users;

    private TenantAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roles = Mockery::mock(RoleRepositoryInterface::class);
        $this->users = Mockery::mock(TenantUserRepositoryInterface::class);

        $catalog = new PermissionCatalog([
            ['key' => 'people.manage', 'name' => 'Gerenciar pessoas', 'base_types' => ['admin']],
            ['key' => 'roles.manage', 'name' => 'Gerenciar perfis', 'base_types' => ['admin']],
            ['key' => 'tickets.answer', 'name' => 'Responder chamados', 'base_types' => ['admin', 'user']],
            ['key' => 'tickets.open', 'name' => 'Abrir chamados', 'base_types' => ['customer']],
            ['name' => 'Entrada sem chave é ignorada', 'base_types' => ['admin']],
        ]);

        $this->service = new TenantAccessService($catalog, $this->roles, $this->users);
    }

    public function test_a_system_role_has_every_permission_of_its_type(): void
    {
        $this->assertSame(['people.manage', 'roles.manage', 'tickets.answer'], $this->service->permissionsOf($this->role(TenantUserType::Admin, system: true)));
        $this->assertSame(['tickets.answer'], $this->service->permissionsOf($this->role(TenantUserType::User, system: true)));
        $this->assertSame(['tickets.open'], $this->service->permissionsOf($this->role(TenantUserType::Customer, system: true)));
    }

    public function test_a_custom_role_has_only_what_was_marked_and_still_applies_to_its_type(): void
    {
        $role = $this->role(TenantUserType::User, permissions: ['tickets.answer', 'people.manage', 'inventada']);

        $this->assertSame(['tickets.answer'], $this->service->permissionsOf($role));
    }

    public function test_a_role_of_unknown_type_permits_nothing(): void
    {
        $role = (new Role)->setRawAttributes(['base_type' => 'qualquer-coisa', 'is_system' => 1]);

        $this->assertSame([], $this->service->permissionsOf($role));
    }

    public function test_only_an_active_person_with_the_permission_is_allowed(): void
    {
        $manager = $this->person($this->role(TenantUserType::Admin, system: true));
        $limited = $this->person($this->role(TenantUserType::Admin, permissions: ['roles.manage']));
        $inactive = $this->person($this->role(TenantUserType::Admin, system: true), active: false);
        $roleless = (new TenantUser)->setRelation('role', null);

        $this->assertTrue($this->service->allows($manager, PermissionCatalog::PEOPLE_MANAGE));
        $this->assertFalse($this->service->allows($limited, PermissionCatalog::PEOPLE_MANAGE));
        $this->assertTrue($this->service->allows($limited, PermissionCatalog::ROLES_MANAGE));
        $this->assertFalse($this->service->allows($inactive, PermissionCatalog::PEOPLE_MANAGE));
        $this->assertFalse($this->service->allows($roleless, PermissionCatalog::PEOPLE_MANAGE));
    }

    public function test_it_counts_people_managers_across_every_role_that_grants_it(): void
    {
        $this->roles->shouldReceive('all')->andReturn(new Collection([
            $this->role(TenantUserType::Admin, system: true, id: 1),
            $this->role(TenantUserType::Admin, permissions: ['people.manage'], id: 2),
            $this->role(TenantUserType::Admin, permissions: ['roles.manage'], id: 3),
            $this->role(TenantUserType::User, system: true, id: 4),
        ]));

        $this->users->shouldReceive('countActiveInRoles')->once()->with([1, 2], 9)->andReturn(1);
        $this->service->assertPeopleManagerRemains(exceptUserId: 9);

        $this->users->shouldReceive('countActiveInRoles')->once()->with([1], null)->andReturn(0);
        $this->expectExceptionObject(TenantUserRuleException::lastPeopleManager());
        $this->service->assertPeopleManagerRemains(exceptRoleId: 2);
    }

    /** @param list<string> $permissions */
    private function role(TenantUserType $type, bool $system = false, array $permissions = [], int $id = 1): Role
    {
        $role = new Role(['name' => 'Perfil', 'base_type' => $type, 'is_system' => $system, 'permissions' => $permissions]);
        $role->id = $id;

        return $role;
    }

    private function person(Role $role, bool $active = true): TenantUser
    {
        return (new TenantUser(['is_active' => $active]))->setRelation('role', $role);
    }
}
