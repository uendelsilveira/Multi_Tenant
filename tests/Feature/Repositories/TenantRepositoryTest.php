<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\DTOs\Tenant\TenantDomainDTO;
use App\Enums\BillingCycle;
use App\Enums\DomainPanel;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Events\TenantCreated;
use Tests\Support\TenantData;
use Tests\TestCase;

final class TenantRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TenantRepositoryInterface $tenants;

    private int $planId;

    protected function setUp(): void
    {
        parent::setUp();

        // Impede a criação real do banco do tenant durante os testes.
        Event::fake([TenantCreated::class]);

        $this->tenants = app(TenantRepositoryInterface::class);
        $this->planId = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []))->id;
    }

    public function test_it_persists_the_tenant_with_pending_domains(): void
    {
        $tenant = $this->tenants->create(TenantData::create(planId: $this->planId, domains: [
            new TenantDomainDTO('painel.acme.test', DomainPanel::Admin),
            new TenantDomainDTO('portal.acme.test', DomainPanel::Customer),
        ]));

        $this->assertDatabaseHas('tenants', [
            'id' => 'acme',
            'legal_name' => 'Acme Ltda',
            'document' => TenantData::VALID_CNPJ,
            'plan_id' => $this->planId,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('domains', ['tenant_id' => 'acme', 'domain' => 'painel.acme.test', 'panel' => 'admin', 'status' => 'pending']);
        $this->assertDatabaseHas('domains', ['tenant_id' => 'acme', 'domain' => 'portal.acme.test', 'panel' => 'customer', 'status' => 'pending']);
        $this->assertCount(2, $tenant->domains);
    }

    public function test_updating_syncs_the_domains(): void
    {
        $tenant = $this->tenants->create(TenantData::create(planId: $this->planId, domains: [
            new TenantDomainDTO('painel.acme.test', DomainPanel::Admin),
            new TenantDomainDTO('antigo.acme.test', DomainPanel::User),
        ]));

        $this->tenants->update($tenant, TenantData::update(planId: $this->planId, domains: [
            new TenantDomainDTO('painel.acme.test', DomainPanel::Admin),
            new TenantDomainDTO('portal.acme.test', DomainPanel::Customer),
        ]));

        $this->assertDatabaseHas('domains', ['tenant_id' => 'acme', 'domain' => 'painel.acme.test']);
        $this->assertDatabaseHas('domains', ['tenant_id' => 'acme', 'domain' => 'portal.acme.test', 'panel' => 'customer']);
        $this->assertDatabaseMissing('domains', ['domain' => 'antigo.acme.test']);
    }

    public function test_uniqueness_checks_consider_deleted_tenants(): void
    {
        $tenant = $this->tenants->create(TenantData::create(planId: $this->planId));
        $this->tenants->softDelete($tenant);

        $this->assertNull($this->tenants->find('acme'));
        $this->assertNotNull($this->tenants->find('acme', withTrashed: true));
        $this->assertTrue($this->tenants->slugExists('acme'));
        $this->assertTrue($this->tenants->documentExists(TenantData::VALID_CNPJ));
        $this->assertFalse($this->tenants->documentExists(TenantData::VALID_CNPJ, 'acme'));
    }

    public function test_hosts_in_use_ignores_the_tenant_itself(): void
    {
        $this->tenants->create(TenantData::create(planId: $this->planId));

        $this->assertSame(['painel.acme.test'], $this->tenants->hostsInUse(['painel.acme.test', 'livre.acme.test']));
        $this->assertSame([], $this->tenants->hostsInUse(['painel.acme.test'], 'acme'));
    }
}
