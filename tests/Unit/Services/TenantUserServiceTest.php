<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\ProvisionalPasswordNotifierInterface;
use App\DTOs\TenantUser\CreateTenantUserDTO;
use App\DTOs\TenantUser\UpdateTenantUserDTO;
use App\Enums\TenantUserType;
use App\Exceptions\Tenant\ProvisionalPasswordException;
use App\Exceptions\TenantUser\TenantUserRuleException;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use App\Services\PermissionCatalog;
use App\Services\TenantAccessService;
use App\Services\TenantUserService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class TenantUserServiceTest extends TestCase
{
    private TenantUserRepositoryInterface&MockInterface $users;

    private RoleRepositoryInterface&MockInterface $roles;

    private ProvisionalPasswordNotifierInterface&MockInterface $notifier;

    private TenantUserService $service;

    private Role $adminRole;

    private Role $userRole;

    private Role $customerRole;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-05 12:00:00');

        $this->users = Mockery::mock(TenantUserRepositoryInterface::class);
        $this->roles = Mockery::mock(RoleRepositoryInterface::class);
        $this->notifier = Mockery::mock(ProvisionalPasswordNotifierInterface::class);

        $this->adminRole = $this->role(1, TenantUserType::Admin);
        $this->userRole = $this->role(2, TenantUserType::User);
        $this->customerRole = $this->role(3, TenantUserType::Customer);

        $this->roles->shouldReceive('find')->with(1)->andReturn($this->adminRole)->byDefault();
        $this->roles->shouldReceive('find')->with(2)->andReturn($this->userRole)->byDefault();
        $this->roles->shouldReceive('find')->with(3)->andReturn($this->customerRole)->byDefault();
        $this->roles->shouldReceive('all')->andReturn(new Collection([$this->adminRole, $this->userRole, $this->customerRole]))->byDefault();
        $this->users->shouldReceive('emailExists')->andReturn(false)->byDefault();

        $catalog = new PermissionCatalog((array) config('permissions.catalog'));

        $this->service = new TenantUserService(
            $this->users,
            $this->roles,
            new TenantAccessService($catalog, $this->roles, $this->users),
            $this->notifier,
        );
    }

    public function test_a_new_person_is_created_without_a_usable_password(): void
    {
        $dto = new CreateTenantUserDTO('Bruno Lima', 'bruno@acme.test', 2);
        $person = new TenantUser;

        $this->users->shouldReceive('createAwaitingAccess')->once()->with($dto)->andReturn($person);
        $this->notifier->shouldNotReceive('send');

        $this->assertSame($person, $this->service->create($dto));
    }

    public function test_it_rejects_an_email_already_in_use(): void
    {
        $this->users->shouldReceive('emailExists')->with('bruno@acme.test')->andReturn(true);
        $this->users->shouldNotReceive('createAwaitingAccess');

        $this->expectExceptionObject(TenantUserRuleException::emailTaken('bruno@acme.test'));

        $this->service->create(new CreateTenantUserDTO('Bruno Lima', 'bruno@acme.test', 2));
    }

    public function test_customers_are_not_registered_through_people_management(): void
    {
        $this->users->shouldNotReceive('createAwaitingAccess');

        $this->expectExceptionObject(TenantUserRuleException::customerRole());

        $this->service->create(new CreateTenantUserDTO('Carla', 'carla@acme.test', 3));
    }

    public function test_nobody_deactivates_their_own_account(): void
    {
        $this->users->shouldNotReceive('setActive');

        $this->expectExceptionObject(TenantUserRuleException::cannotDeactivateSelf());

        $this->service->deactivate(7, 7);
    }

    public function test_the_last_people_manager_is_not_deactivated(): void
    {
        $admin = $this->person(7, $this->adminRole);

        $this->users->shouldReceive('find')->with(7)->andReturn($admin);
        $this->users->shouldReceive('countActiveInRoles')->with([1], 7)->andReturn(0);
        $this->users->shouldNotReceive('setActive');

        $this->expectExceptionObject(TenantUserRuleException::lastPeopleManager());

        $this->service->deactivate(7, 8);
    }

    public function test_a_people_manager_is_deactivated_when_another_remains_and_others_without_checking(): void
    {
        $admin = $this->person(7, $this->adminRole);
        $user = $this->person(9, $this->userRole);

        $this->users->shouldReceive('find')->with(7)->andReturn($admin);
        $this->users->shouldReceive('find')->with(9)->andReturn($user);
        $this->users->shouldReceive('countActiveInRoles')->once()->with([1], 7)->andReturn(1);
        $this->users->shouldReceive('setActive')->once()->with($admin, false);
        $this->users->shouldReceive('setActive')->once()->with($user, false);

        $this->service->deactivate(7, 8);
        $this->service->deactivate(9, 8);
    }

    public function test_the_last_people_manager_cannot_be_moved_to_a_role_without_that_permission(): void
    {
        $admin = $this->person(7, $this->adminRole);

        $this->users->shouldReceive('find')->with(7)->andReturn($admin);
        $this->users->shouldReceive('countActiveInRoles')->with([1], 7)->andReturn(0);
        $this->users->shouldNotReceive('update');

        $this->expectExceptionObject(TenantUserRuleException::lastPeopleManager());

        $this->service->update(new UpdateTenantUserDTO(7, 'Ana', 'ana@acme.test', 2));
    }

    public function test_a_provisional_password_is_issued_for_24_hours_and_sent(): void
    {
        $person = $this->person(9, $this->userRole, mustChange: true);
        $tenant = new Tenant;
        $issued = [];

        $this->users->shouldReceive('find')->with(9)->andReturn($person);
        $this->users->shouldReceive('setProvisionalPassword')->once()
            ->andReturnUsing(function (TenantUser $user, string $password, CarbonInterface $expiresAt) use (&$issued): void {
                $issued = compact('password', 'expiresAt');
            });
        $this->notifier->shouldReceive('send')->once()
            ->withArgs(function (TenantUser $to, Tenant $of, string $password, CarbonInterface $expiresAt) use ($person, $tenant, &$issued): bool {
                return $to === $person && $of === $tenant && $password === $issued['password'] && $expiresAt->equalTo($issued['expiresAt']);
            });

        $this->service->issueProvisionalPassword(9, $tenant);

        $this->assertSame(16, strlen($issued['password']));
        $this->assertTrue($issued['expiresAt']->equalTo(Carbon::now()->addHours(24)));
    }

    public function test_no_provisional_password_after_the_first_access_or_for_a_deactivated_person(): void
    {
        $this->users->shouldReceive('find')->with(9)->andReturn($this->person(9, $this->userRole, mustChange: false));
        $this->users->shouldReceive('find')->with(10)->andReturn($this->person(10, $this->userRole, mustChange: true, active: false));
        $this->users->shouldNotReceive('setProvisionalPassword');
        $this->notifier->shouldNotReceive('send');

        try {
            $this->service->issueProvisionalPassword(9, new Tenant);
            $this->fail('Depois do primeiro acesso não se emite senha provisória.');
        } catch (ProvisionalPasswordException $e) {
            $this->assertEquals(ProvisionalPasswordException::alreadyUsed(), $e);
        }

        $this->expectExceptionObject(TenantUserRuleException::inactive());
        $this->service->issueProvisionalPassword(10, new Tenant);
    }

    public function test_a_provisional_password_expires_at_its_deadline(): void
    {
        $valid = new TenantUser(['must_change_password' => true, 'password_expires_at' => Carbon::now()->addMinute()]);
        $expired = new TenantUser(['must_change_password' => true, 'password_expires_at' => Carbon::now()->subMinute()]);
        $definitive = new TenantUser(['must_change_password' => false, 'password_expires_at' => Carbon::now()->subMinute()]);

        $this->assertFalse($this->service->provisionalPasswordExpired($valid));
        $this->assertTrue($this->service->provisionalPasswordExpired($expired));
        $this->assertFalse($this->service->provisionalPasswordExpired($definitive), 'Senha definitiva não expira.');
    }

    public function test_it_changes_a_pending_provisional_password(): void
    {
        $user = new TenantUser(['must_change_password' => true, 'password_expires_at' => Carbon::now()->addHour()]);

        $this->users->shouldReceive('changePassword')->once()->with($user, 'NovaSenha123');

        $this->service->changeProvisionalPassword($user, 'NovaSenha123');
    }

    public function test_it_refuses_the_change_when_there_is_nothing_pending_or_it_expired(): void
    {
        $this->users->shouldNotReceive('changePassword');

        try {
            $this->service->changeProvisionalPassword(new TenantUser(['must_change_password' => false]), 'NovaSenha123');
            $this->fail('Sem pendência não há troca de senha provisória.');
        } catch (ProvisionalPasswordException $e) {
            $this->assertEquals(ProvisionalPasswordException::notPending(), $e);
        }

        $this->expectExceptionObject(ProvisionalPasswordException::expired());

        $this->service->changeProvisionalPassword(
            new TenantUser(['must_change_password' => true, 'password_expires_at' => Carbon::now()->subSecond()]),
            'NovaSenha123',
        );
    }

    private function role(int $id, TenantUserType $type): Role
    {
        $role = new Role(['name' => $type->label(), 'base_type' => $type, 'is_system' => true]);
        $role->id = $id;

        return $role;
    }

    private function person(int $id, Role $role, bool $mustChange = false, bool $active = true): TenantUser
    {
        $person = new TenantUser(['name' => 'Pessoa', 'email' => 'pessoa@acme.test', 'must_change_password' => $mustChange, 'is_active' => $active]);
        $person->id = $id;
        $person->role_id = $role->id;

        return $person->setRelation('role', $role);
    }
}
