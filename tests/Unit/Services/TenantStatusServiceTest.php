<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Tenant\ChangeTenantStatusDTO;
use App\Enums\TenantStatus;
use App\Enums\TenantStatusSource;
use App\Exceptions\Tenant\TenantNotFoundException;
use App\Exceptions\Tenant\TenantStatusException;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\TenantStatusService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class TenantStatusServiceTest extends TestCase
{
    private TenantRepositoryInterface&MockInterface $tenants;

    private TenantStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-05 12:00:00');
        CarbonImmutable::setTestNow('2026-10-05 12:00:00');

        $this->tenants = Mockery::mock(TenantRepositoryInterface::class);
        $this->service = new TenantStatusService($this->tenants);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_the_central_suspends_a_tenant_with_a_reason_and_the_author_is_recorded(): void
    {
        $tenant = $this->tenant(TenantStatus::Active);

        $this->tenants->shouldReceive('find')->with('acme')->andReturn($tenant);
        $this->tenants->shouldReceive('changeStatus')->once()
            ->with($tenant, TenantStatus::Suspended, TenantStatusSource::Manual, 7, 'Inadimplência há 30 dias.', null);

        $this->assertSame($tenant, $this->service->changeManually($this->dto(TenantStatus::Suspended, 'Inadimplência há 30 dias.')));
    }

    public function test_a_manual_change_requires_a_reason(): void
    {
        $this->tenants->shouldReceive('find')->andReturn($this->tenant(TenantStatus::Active));
        $this->tenants->shouldNotReceive('changeStatus');

        $this->expectExceptionObject(TenantStatusException::reasonRequired());

        $this->service->changeManually($this->dto(TenantStatus::Suspended, ''));
    }

    public function test_a_lock_must_end_in_the_future(): void
    {
        $this->tenants->shouldReceive('find')->andReturn($this->tenant(TenantStatus::Suspended));
        $this->tenants->shouldNotReceive('changeStatus');

        $this->expectExceptionObject(TenantStatusException::lockMustBeInTheFuture());

        $this->service->changeManually($this->dto(TenantStatus::Active, 'Acordo fechado.', CarbonImmutable::now()->subMinute()));
    }

    public function test_changing_nothing_is_refused_but_changing_only_the_lock_is_accepted(): void
    {
        $tenant = $this->tenant(TenantStatus::Active);
        $until = CarbonImmutable::now()->addDays(10);

        $this->tenants->shouldReceive('find')->andReturn($tenant);
        $this->tenants->shouldReceive('changeStatus')->once()
            ->with($tenant, TenantStatus::Active, TenantStatusSource::Manual, 7, 'Prazo negociado.', $until);

        // Mesma situação, agora com trava: é uma alteração.
        $this->service->changeManually($this->dto(TenantStatus::Active, 'Prazo negociado.', $until));

        $this->expectExceptionObject(TenantStatusException::nothingToChange());

        $this->service->changeManually($this->dto(TenantStatus::Active, 'Sem mudança.'));
    }

    public function test_the_gateway_changes_the_status_when_nothing_is_locked(): void
    {
        $tenant = $this->tenant(TenantStatus::Active);

        $this->tenants->shouldReceive('find')->andReturn($tenant);
        $this->tenants->shouldReceive('changeStatus')->once()
            ->with($tenant, TenantStatus::Suspended, TenantStatusSource::Gateway, null, 'Pagamento vencido.', null);

        $this->assertTrue($this->service->changeFromGateway('acme', TenantStatus::Suspended, 'Pagamento vencido.'));
    }

    public function test_the_gateway_does_not_touch_a_locked_tenant_until_the_lock_expires(): void
    {
        $locked = $this->tenant(TenantStatus::Active, lockedUntil: Carbon::now()->addDay());
        $expired = $this->tenant(TenantStatus::Active, lockedUntil: Carbon::now()->subMinute());

        $this->tenants->shouldReceive('find')->andReturn($locked, $expired);
        $this->tenants->shouldReceive('changeStatus')->once()
            ->with($expired, TenantStatus::Suspended, TenantStatusSource::Gateway, null, 'Pagamento vencido.', null);

        $this->assertFalse($this->service->changeFromGateway('acme', TenantStatus::Suspended, 'Pagamento vencido.'));
        $this->assertTrue($this->service->changeFromGateway('acme', TenantStatus::Suspended, 'Pagamento vencido.'));
    }

    public function test_the_gateway_does_not_record_a_change_to_the_same_status(): void
    {
        $this->tenants->shouldReceive('find')->andReturn($this->tenant(TenantStatus::Active));
        $this->tenants->shouldNotReceive('changeStatus');

        $this->assertFalse($this->service->changeFromGateway('acme', TenantStatus::Active, 'Pagamento confirmado.'));
    }

    public function test_it_fails_for_a_tenant_that_does_not_exist(): void
    {
        $this->tenants->shouldReceive('find')->with('ninguem')->andReturn(null);

        $this->expectException(TenantNotFoundException::class);

        $this->service->changeFromGateway('ninguem', TenantStatus::Suspended, 'x');
    }

    private function dto(TenantStatus $status, string $reason, ?CarbonImmutable $lockedUntil = null): ChangeTenantStatusDTO
    {
        return new ChangeTenantStatusDTO('acme', $status, $reason, $lockedUntil, 7);
    }

    private function tenant(TenantStatus $status, ?Carbon $lockedUntil = null): Tenant
    {
        $tenant = new Tenant;
        $tenant->id = 'acme';
        $tenant->status = $status;
        $tenant->status_locked_until = $lockedUntil;

        return $tenant;
    }
}
