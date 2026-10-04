<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Tenant\TenantDomainDTO;
use App\Enums\BillingCycle;
use App\Enums\DomainPanel;
use App\Exceptions\Plan\PlanNotAvailableException;
use App\Exceptions\Tenant\InvalidTenantDataException;
use App\Exceptions\Tenant\InvalidTenantDomainsException;
use App\Exceptions\Tenant\TenantAlreadyExistsException;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Tenant;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\DocumentValidator;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\Support\TenantData;
use Tests\TestCase;

final class TenantServiceTest extends TestCase
{
    private TenantRepositoryInterface&MockInterface $tenants;

    private PlanRepositoryInterface&MockInterface $plans;

    private TenantService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenants = Mockery::mock(TenantRepositoryInterface::class);
        $this->plans = Mockery::mock(PlanRepositoryInterface::class);

        // Cenário padrão: nada em conflito e um plano ativo com ciclo mensal.
        $this->tenants->shouldReceive('slugExists')->andReturn(false)->byDefault();
        $this->tenants->shouldReceive('documentExists')->andReturn(false)->byDefault();
        $this->tenants->shouldReceive('hostsInUse')->andReturn([])->byDefault();
        $this->plans->shouldReceive('find')->andReturn($this->plan())->byDefault();

        $this->service = new TenantService($this->tenants, $this->plans, new DocumentValidator, ['localhost']);
    }

    public function test_it_creates_the_tenant_when_every_rule_passes(): void
    {
        $dto = TenantData::create();
        $tenant = new Tenant;

        $this->tenants->shouldReceive('create')->once()->with($dto)->andReturn($tenant);

        $this->assertSame($tenant, $this->service->create($dto));
    }

    public function test_it_rejects_a_slug_outside_the_format(): void
    {
        $this->tenants->shouldNotReceive('create');

        foreach (['ab', 'Acme', '1acme', 'acme_ltda', 'acme ltda'] as $slug) {
            try {
                $this->service->create(TenantData::create(slug: $slug));
                $this->fail("O slug [{$slug}] deveria ter sido rejeitado.");
            } catch (InvalidTenantDataException $e) {
                $this->assertStringContainsString($slug, $e->getMessage());
            }
        }
    }

    public function test_it_rejects_a_slug_already_taken_even_by_a_deleted_tenant(): void
    {
        $this->tenants->shouldReceive('slugExists')->with('acme')->andReturn(true);
        $this->tenants->shouldNotReceive('create');

        $this->expectException(TenantAlreadyExistsException::class);

        $this->service->create(TenantData::create());
    }

    public function test_it_rejects_an_invalid_document(): void
    {
        $this->tenants->shouldNotReceive('create');

        $this->expectException(InvalidTenantDataException::class);

        $this->service->create(TenantData::create(company: TenantData::company('11222333000182')));
    }

    public function test_it_rejects_a_document_already_registered(): void
    {
        $this->tenants->shouldReceive('documentExists')->andReturn(true);
        $this->tenants->shouldNotReceive('create');

        $this->expectException(TenantAlreadyExistsException::class);

        $this->service->create(TenantData::create());
    }

    public function test_it_rejects_an_inactive_plan(): void
    {
        $this->plans->shouldReceive('find')->andReturn($this->plan(active: false));
        $this->tenants->shouldNotReceive('create');

        $this->expectException(PlanNotAvailableException::class);

        $this->service->create(TenantData::create());
    }

    public function test_it_rejects_a_cycle_the_plan_does_not_offer(): void
    {
        $this->tenants->shouldNotReceive('create');

        $this->expectException(PlanNotAvailableException::class);

        $this->service->create(TenantData::create(cycle: BillingCycle::Annual));
    }

    public function test_it_requires_at_least_one_domain(): void
    {
        $this->tenants->shouldNotReceive('create');

        $this->expectException(InvalidTenantDomainsException::class);

        $this->service->create(TenantData::create(domains: []));
    }

    public function test_it_requires_a_domain_pointing_to_the_admin_panel(): void
    {
        $this->tenants->shouldNotReceive('create');

        $this->expectExceptionObject(InvalidTenantDomainsException::missingAdminPanel());

        $this->service->create(TenantData::create(domains: [
            new TenantDomainDTO('app.acme.test', DomainPanel::User),
            new TenantDomainDTO('portal.acme.test', DomainPanel::Customer),
        ]));
    }

    public function test_it_rejects_a_host_that_is_not_a_full_domain(): void
    {
        $this->tenants->shouldNotReceive('create');

        foreach (['acme', 'http://acme.test', 'acme.test/admin', 'Acme.Test', '-acme.test'] as $host) {
            try {
                $this->service->create(TenantData::create(domains: [new TenantDomainDTO($host, DomainPanel::Admin)]));
                $this->fail("O host [{$host}] deveria ter sido rejeitado.");
            } catch (InvalidTenantDomainsException $e) {
                $this->assertStringContainsString($host, $e->getMessage());
            }
        }
    }

    public function test_it_rejects_a_duplicated_host(): void
    {
        $this->tenants->shouldNotReceive('create');

        $this->expectExceptionObject(InvalidTenantDomainsException::duplicated('painel.acme.test'));

        $this->service->create(TenantData::create(domains: [
            new TenantDomainDTO('painel.acme.test', DomainPanel::Admin),
            new TenantDomainDTO('painel.acme.test', DomainPanel::User),
        ]));
    }

    public function test_it_rejects_a_central_domain(): void
    {
        $service = new TenantService($this->tenants, $this->plans, new DocumentValidator, ['central.test']);
        $this->tenants->shouldNotReceive('create');

        $this->expectExceptionObject(InvalidTenantDomainsException::central('central.test'));

        $service->create(TenantData::create(domains: [new TenantDomainDTO('central.test', DomainPanel::Admin)]));
    }

    public function test_it_rejects_a_host_already_used_by_another_tenant(): void
    {
        $this->tenants->shouldReceive('hostsInUse')->andReturn(['painel.acme.test']);
        $this->tenants->shouldNotReceive('create');

        $this->expectExceptionObject(InvalidTenantDomainsException::alreadyInUse('painel.acme.test'));

        $this->service->create(TenantData::create());
    }

    public function test_a_tenant_may_stay_on_its_plan_after_the_plan_becomes_inactive(): void
    {
        $tenant = new Tenant;
        $tenant->id = 'acme';
        $tenant->plan_id = 1;
        $dto = TenantData::update();

        $this->tenants->shouldReceive('find')->with('acme')->andReturn($tenant);
        $this->plans->shouldReceive('find')->andReturn($this->plan(active: false));
        $this->tenants->shouldReceive('update')->once()->with($tenant, $dto)->andReturn($tenant);

        $this->assertSame($tenant, $this->service->update($dto));
    }

    public function test_restoring_a_tenant_that_is_not_deleted_does_nothing(): void
    {
        $tenant = new Tenant;
        $tenant->id = 'acme';

        $this->tenants->shouldReceive('find')->with('acme', true)->andReturn($tenant);
        $this->tenants->shouldNotReceive('restore');

        $this->assertSame($tenant, $this->service->restore('acme'));
    }

    private function plan(bool $active = true): Plan
    {
        $plan = new Plan(['name' => 'Profissional', 'is_active' => $active]);
        $plan->id = 1;
        $plan->setRelation('prices', new Collection([
            new PlanPrice(['billing_cycle' => BillingCycle::Monthly->value, 'price' => '99.90']),
        ]));

        return $plan;
    }
}
