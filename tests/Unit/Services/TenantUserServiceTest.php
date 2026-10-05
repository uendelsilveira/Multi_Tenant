<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\Tenant\ProvisionalPasswordException;
use App\Models\TenantUser;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use App\Services\TenantUserService;
use Illuminate\Support\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class TenantUserServiceTest extends TestCase
{
    private TenantUserRepositoryInterface&MockInterface $users;

    private TenantUserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-04 12:00:00');

        $this->users = Mockery::mock(TenantUserRepositoryInterface::class);
        $this->service = new TenantUserService($this->users);
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

    public function test_it_refuses_the_change_when_there_is_nothing_pending(): void
    {
        $this->users->shouldNotReceive('changePassword');

        $this->expectExceptionObject(ProvisionalPasswordException::notPending());

        $this->service->changeProvisionalPassword(new TenantUser(['must_change_password' => false]), 'NovaSenha123');
    }

    public function test_it_refuses_the_change_when_the_provisional_password_expired(): void
    {
        $this->users->shouldNotReceive('changePassword');

        $this->expectExceptionObject(ProvisionalPasswordException::expired());

        $this->service->changeProvisionalPassword(
            new TenantUser(['must_change_password' => true, 'password_expires_at' => Carbon::now()->subSecond()]),
            'NovaSenha123',
        );
    }
}
