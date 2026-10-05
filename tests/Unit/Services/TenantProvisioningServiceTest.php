<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\ProvisionalPasswordNotifierInterface;
use App\Contracts\TenantEnvironmentInterface;
use App\Enums\ProvisioningStatus;
use App\Exceptions\Tenant\ProvisionalPasswordException;
use App\Exceptions\Tenant\TenantProvisioningException;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\TenantUserRepositoryInterface;
use App\Services\TenantProvisioningService;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class TenantProvisioningServiceTest extends TestCase
{
    private TenantRepositoryInterface&MockInterface $tenants;

    private TenantUserRepositoryInterface&MockInterface $users;

    private TenantEnvironmentInterface&MockInterface $environment;

    private ProvisionalPasswordNotifierInterface&MockInterface $notifier;

    private TenantProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-04 12:00:00');

        $this->tenants = Mockery::mock(TenantRepositoryInterface::class);
        $this->users = Mockery::mock(TenantUserRepositoryInterface::class);
        $this->environment = Mockery::mock(TenantEnvironmentInterface::class);
        $this->notifier = Mockery::mock(ProvisionalPasswordNotifierInterface::class);

        // A troca de contexto é infraestrutura: aqui apenas executa o callback.
        $this->environment->shouldReceive('run')
            ->andReturnUsing(fn (Tenant $tenant, Closure $callback): mixed => $callback())
            ->byDefault();

        $this->service = new TenantProvisioningService($this->tenants, $this->users, $this->environment, $this->notifier);
    }

    public function test_it_prepares_the_database_and_creates_the_first_admin_from_the_contact(): void
    {
        $tenant = $this->tenant(ProvisioningStatus::Pending);
        $admin = new TenantUser;
        $created = [];

        $this->tenants->shouldReceive('find')->with('acme')->andReturn($tenant);
        $this->tenants->shouldReceive('updateProvisioning')->once()->with($tenant, ProvisioningStatus::Provisioning)->ordered();
        $this->environment->shouldReceive('ensureDatabaseExists')->once()->with($tenant)->ordered();
        $this->environment->shouldReceive('migrate')->once()->with($tenant)->ordered();
        $this->users->shouldReceive('findInitialAdmin')->once()->andReturn(null);
        $this->users->shouldReceive('createInitialAdmin')->once()
            ->andReturnUsing(function (string $name, string $email, string $password, CarbonInterface $expiresAt) use ($admin, &$created): TenantUser {
                $created = compact('name', 'email', 'password', 'expiresAt');

                return $admin;
            });
        $this->notifier->shouldReceive('send')->once()
            ->withArgs(function (TenantUser $to, Tenant $of, string $password, CarbonInterface $expiresAt) use ($admin, $tenant, &$created): bool {
                return $to === $admin
                    && $of === $tenant
                    && $password === $created['password']
                    && $expiresAt->equalTo($created['expiresAt']);
            });
        $this->tenants->shouldReceive('updateProvisioning')->once()->with($tenant, ProvisioningStatus::Ready)->ordered();

        $this->service->provision('acme');

        $this->assertSame('Ana Souza', $created['name']);
        $this->assertSame('ana@acme.test', $created['email']);
        $this->assertSame(16, strlen($created['password']));
        $this->assertTrue($created['expiresAt']->equalTo(Carbon::now()->addHours(24)), 'A senha provisória vale 24 horas.');
    }

    public function test_running_again_does_not_create_or_notify_a_second_admin(): void
    {
        $tenant = $this->tenant(ProvisioningStatus::Failed);

        $this->tenants->shouldReceive('find')->andReturn($tenant);
        $this->tenants->shouldReceive('updateProvisioning')->twice();
        $this->environment->shouldReceive('ensureDatabaseExists')->once();
        $this->environment->shouldReceive('migrate')->once();
        $this->users->shouldReceive('findInitialAdmin')->andReturn(new TenantUser);
        $this->users->shouldNotReceive('createInitialAdmin');
        $this->notifier->shouldNotReceive('send');

        $this->service->provision('acme');
    }

    public function test_it_refuses_a_tenant_without_contact_before_touching_anything(): void
    {
        $tenant = $this->tenant(ProvisioningStatus::Pending);
        $tenant->contact_email = null;

        $this->tenants->shouldReceive('find')->andReturn($tenant);
        $this->tenants->shouldNotReceive('updateProvisioning');
        $this->environment->shouldNotReceive('ensureDatabaseExists');

        $this->expectException(TenantProvisioningException::class);

        $this->service->provision('acme');
    }

    public function test_a_failure_is_recorded_unless_the_tenant_is_already_ready(): void
    {
        $failing = $this->tenant(ProvisioningStatus::Provisioning);
        $ready = $this->tenant(ProvisioningStatus::Ready);

        $this->tenants->shouldReceive('find')->with('acme', true)->andReturn($failing, $ready);
        $this->tenants->shouldReceive('updateProvisioning')->once()->with($failing, ProvisioningStatus::Failed, 'banco indisponível');

        $this->service->markFailed('acme', 'banco indisponível');
        $this->service->markFailed('acme', 'não deve ser gravado');
    }

    public function test_a_ready_tenant_cannot_be_provisioned_again_on_request(): void
    {
        $this->tenants->shouldReceive('find')->andReturn($this->tenant(ProvisioningStatus::Ready));

        $this->expectExceptionObject(TenantProvisioningException::alreadyProvisioned('acme'));

        $this->service->assertCanRetry('acme');
    }

    public function test_any_tenant_that_is_not_ready_can_be_provisioned_again(): void
    {
        foreach ([ProvisioningStatus::Pending, ProvisioningStatus::Provisioning, ProvisioningStatus::Failed] as $status) {
            $tenants = Mockery::mock(TenantRepositoryInterface::class);
            $tenants->shouldReceive('find')->andReturn($this->tenant($status));
            $service = new TenantProvisioningService($tenants, $this->users, $this->environment, $this->notifier);

            $this->assertSame($status, $service->assertCanRetry('acme')->provisioning_status);
        }
    }

    public function test_resending_generates_a_new_provisional_password(): void
    {
        $tenant = $this->tenant(ProvisioningStatus::Ready);
        $admin = new TenantUser(['must_change_password' => true]);

        $this->tenants->shouldReceive('find')->andReturn($tenant);
        $this->users->shouldReceive('findInitialAdmin')->andReturn($admin);
        $this->users->shouldReceive('setProvisionalPassword')->once()
            ->withArgs(fn (TenantUser $user, string $password, CarbonInterface $expiresAt): bool => $user === $admin
                && strlen($password) === 16
                && $expiresAt->equalTo(Carbon::now()->addHours(24)));
        $this->notifier->shouldReceive('send')->once();

        $this->service->resendProvisionalPassword('acme');
    }

    public function test_resending_is_refused_after_the_first_access(): void
    {
        $this->tenants->shouldReceive('find')->andReturn($this->tenant(ProvisioningStatus::Ready));
        $this->users->shouldReceive('findInitialAdmin')->andReturn(new TenantUser(['must_change_password' => false]));
        $this->users->shouldNotReceive('setProvisionalPassword');
        $this->notifier->shouldNotReceive('send');

        $this->expectExceptionObject(ProvisionalPasswordException::alreadyUsed());

        $this->service->assertCanResendPassword('acme');
    }

    public function test_resending_is_refused_while_the_tenant_is_not_ready(): void
    {
        $this->tenants->shouldReceive('find')->andReturn($this->tenant(ProvisioningStatus::Failed));
        $this->users->shouldNotReceive('findInitialAdmin');

        $this->expectExceptionObject(ProvisionalPasswordException::tenantNotReady('acme'));

        $this->service->assertCanResendPassword('acme');
    }

    private function tenant(ProvisioningStatus $status): Tenant
    {
        $tenant = new Tenant;
        $tenant->id = 'acme';
        $tenant->contact_name = 'Ana Souza';
        $tenant->contact_email = 'ana@acme.test';
        $tenant->provisioning_status = $status;

        return $tenant;
    }
}
