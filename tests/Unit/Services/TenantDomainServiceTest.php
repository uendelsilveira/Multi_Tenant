<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\DnsLookupInterface;
use App\Enums\DomainPanel;
use App\Enums\DomainStatus;
use App\Exceptions\TenantDomain\TenantDomainAlreadyVerifiedException;
use App\Exceptions\TenantDomain\TenantDomainNotFoundException;
use App\Models\Domain;
use App\Models\Tenant;
use App\Repositories\Contracts\DomainRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\TenantDomainService;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class TenantDomainServiceTest extends TestCase
{
    private DomainRepositoryInterface&MockInterface $domains;

    private TenantRepositoryInterface&MockInterface $tenants;

    private DnsLookupInterface&MockInterface $dns;

    private TenantDomainService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->domains = Mockery::mock(DomainRepositoryInterface::class);
        $this->tenants = Mockery::mock(TenantRepositoryInterface::class);
        $this->dns = Mockery::mock(DnsLookupInterface::class);

        $this->service = new TenantDomainService($this->domains, $this->tenants, $this->dns, new Repository(new ArrayStore));
    }

    public function test_a_verified_domain_resolves_to_its_tenant_and_panel(): void
    {
        $tenant = new Tenant;

        $this->domains->shouldReceive('findByHost')->with('app.acme.test')->andReturn($this->domain(DomainStatus::Active, DomainPanel::User));
        $this->tenants->shouldReceive('find')->with('acme')->andReturn($tenant);

        $resolved = $this->service->resolve('app.acme.test');

        $this->assertNotNull($resolved);
        $this->assertSame($tenant, $resolved->tenant);
        $this->assertSame(DomainPanel::User, $resolved->panel);
    }

    public function test_an_unknown_or_pending_domain_does_not_resolve(): void
    {
        $this->domains->shouldReceive('findByHost')->with('ninguem.test')->andReturn(null);
        $this->domains->shouldReceive('findByHost')->with('pendente.acme.test')->andReturn($this->domain(DomainStatus::Pending));
        $this->tenants->shouldNotReceive('find');

        $this->assertNull($this->service->resolve('ninguem.test'));
        $this->assertNull($this->service->resolve('pendente.acme.test'));
    }

    public function test_a_domain_of_a_deleted_tenant_does_not_resolve(): void
    {
        $this->domains->shouldReceive('findByHost')->andReturn($this->domain(DomainStatus::Active));
        $this->tenants->shouldReceive('find')->with('acme')->andReturn(null);

        $this->assertNull($this->service->resolve('painel.acme.test'));
    }

    public function test_the_domain_lookup_is_cached_until_forgotten(): void
    {
        $this->domains->shouldReceive('findByHost')->twice()->andReturn($this->domain(DomainStatus::Active));
        $this->tenants->shouldReceive('find')->andReturn(new Tenant);

        $this->service->resolve('painel.acme.test');
        $this->service->resolve('painel.acme.test');
        $this->service->resolve('painel.acme.test');

        $this->service->forget('painel.acme.test');

        $this->service->resolve('painel.acme.test');
    }

    public function test_a_pending_domain_is_never_cached_so_verification_takes_effect_at_once(): void
    {
        $this->domains->shouldReceive('findByHost')->once()->andReturn($this->domain(DomainStatus::Pending));
        $this->domains->shouldReceive('findByHost')->once()->andReturn($this->domain(DomainStatus::Active));
        $this->tenants->shouldReceive('find')->andReturn(new Tenant);

        $this->assertNull($this->service->resolve('painel.acme.test'));
        $this->assertNotNull($this->service->resolve('painel.acme.test'));
    }

    public function test_verifying_records_who_did_it(): void
    {
        $domain = $this->domain(DomainStatus::Pending);

        $this->domains->shouldReceive('find')->with(7)->andReturn($domain);
        $this->domains->shouldReceive('markVerified')->once()->with($domain, 42);

        $this->assertSame($domain, $this->service->verify(7, 42));
    }

    public function test_a_verified_domain_is_not_verified_twice(): void
    {
        $this->domains->shouldReceive('find')->andReturn($this->domain(DomainStatus::Active));
        $this->domains->shouldNotReceive('markVerified');

        $this->expectException(TenantDomainAlreadyVerifiedException::class);

        $this->service->verify(7, 42);
    }

    public function test_it_fails_for_a_domain_that_does_not_exist(): void
    {
        $this->domains->shouldReceive('find')->with(99)->andReturn(null);

        $this->expectException(TenantDomainNotFoundException::class);

        $this->service->dnsRecords(99);
    }

    public function test_dns_records_come_from_the_lookup_of_the_domain_host(): void
    {
        $this->domains->shouldReceive('find')->andReturn($this->domain(DomainStatus::Pending));
        $this->dns->shouldReceive('lookup')->once()->with('painel.acme.test')->andReturn(['A 203.0.113.10']);

        $this->assertSame(['A 203.0.113.10'], $this->service->dnsRecords(7));
    }

    private function domain(DomainStatus $status, DomainPanel $panel = DomainPanel::Admin): Domain
    {
        return (new Domain)->setRawAttributes([
            'id' => 7,
            'domain' => 'painel.acme.test',
            'tenant_id' => 'acme',
            'panel' => $panel->value,
            'status' => $status->value,
        ]);
    }
}
