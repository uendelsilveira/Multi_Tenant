<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Actions\Tenant\SoftDeleteTenantAction;
use App\Actions\TenantDomain\VerifyTenantDomainAction;
use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\DTOs\Tenant\TenantDomainDTO;
use App\Enums\BillingCycle;
use App\Enums\DomainPanel;
use App\Enums\ProvisioningStatus;
use App\Enums\UserRole;
use App\Http\Middleware\EnsureTenantIsProvisioned;
use App\Http\Middleware\InitializeTenancyForTenantDomain;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\Support\TenantData;
use Tests\TestCase;

/**
 * Resolução por domínio (RF08): só domínio verificado responde, e cada domínio
 * serve um único painel. Nenhum banco de tenant é usado aqui.
 */
final class TenantDomainRoutingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private int $centralUserId;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $planId = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []))->id;

        $tenants = app(TenantRepositoryInterface::class);

        $this->tenant = $tenants->create(TenantData::create(planId: $planId, domains: [
            new TenantDomainDTO('painel.acme.test', DomainPanel::Admin),
            new TenantDomainDTO('app.acme.test', DomainPanel::User),
            new TenantDomainDTO('portal.acme.test', DomainPanel::Customer),
        ]));
        $tenants->updateProvisioning($this->tenant, ProvisioningStatus::Ready);

        $this->centralUserId = User::factory()->create(['role' => UserRole::Admin])->id;
    }

    protected function tearDown(): void
    {
        // A requisição deixa a tenancy inicializada; volta ao contexto central antes do rollback.
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_a_pending_domain_behaves_as_if_it_did_not_exist(): void
    {
        $this->get('http://painel.acme.test/')->assertNotFound();
        $this->get('http://painel.acme.test/admin/login')->assertNotFound();
    }

    public function test_once_verified_each_domain_leads_to_its_own_panel(): void
    {
        $this->verifyAllDomains();

        $this->get('http://painel.acme.test/')->assertRedirect('/admin');
        $this->endTenancy();
        $this->get('http://app.acme.test/')->assertRedirect('/app');
        $this->endTenancy();
        $this->get('http://portal.acme.test/')->assertRedirect('/portal');
    }

    public function test_the_path_of_another_panel_does_not_exist_on_a_domain(): void
    {
        $this->verifyAllDomains();

        $this->get('http://painel.acme.test/app/login')->assertNotFound();
        $this->endTenancy();
        $this->get('http://painel.acme.test/portal/login')->assertNotFound();
        $this->endTenancy();
        $this->get('http://app.acme.test/admin/login')->assertNotFound();
        $this->endTenancy();
        $this->get('http://portal.acme.test/app/login')->assertNotFound();
    }

    public function test_verifying_one_domain_does_not_open_the_others(): void
    {
        $domain = $this->tenant->domains->firstWhere('domain', 'app.acme.test');

        app(VerifyTenantDomainAction::class)->execute($domain->id, $this->centralUserId);

        $this->get('http://app.acme.test/')->assertRedirect('/app');
        $this->endTenancy();
        $this->get('http://painel.acme.test/')->assertNotFound();
    }

    public function test_deleting_the_tenant_closes_its_domains_at_once_even_if_cached(): void
    {
        $this->verifyAllDomains();

        // Aquece o cache de resolução.
        $this->get('http://painel.acme.test/')->assertRedirect('/admin');
        $this->endTenancy();

        app(SoftDeleteTenantAction::class)->execute($this->tenant->id);

        $this->get('http://painel.acme.test/')->assertNotFound();
    }

    public function test_changing_the_host_of_a_domain_closes_the_old_address(): void
    {
        $this->verifyAllDomains();

        $this->get('http://app.acme.test/')->assertRedirect('/app');
        $this->endTenancy();

        // Trocar o endereço cria um domínio novo, pendente; o antigo deixa de existir.
        app(TenantRepositoryInterface::class)->update($this->tenant, TenantData::update(planId: (int) $this->tenant->plan_id, domains: [
            new TenantDomainDTO('painel.acme.test', DomainPanel::Admin),
            new TenantDomainDTO('novo.acme.test', DomainPanel::User),
        ]));

        $this->get('http://app.acme.test/')->assertNotFound();
        $this->get('http://novo.acme.test/')->assertNotFound();
    }

    public function test_the_livewire_update_route_resolves_the_tenant_before_the_session(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route): bool => in_array('POST', $route->methods(), true) && str_contains($route->uri(), 'livewire') && str_contains($route->uri(), 'update'));

        $this->assertNotNull($route, 'A rota de atualização do Livewire deveria existir.');

        $middleware = $route->gatherMiddleware();

        $this->assertContains(InitializeTenancyForTenantDomain::class, $middleware);
        $this->assertContains(EnsureTenantIsProvisioned::class, $middleware);
    }

    private function verifyAllDomains(): void
    {
        foreach ($this->tenant->domains as $domain) {
            app(VerifyTenantDomainAction::class)->execute($domain->id, $this->centralUserId);
        }
    }

    private function endTenancy(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
    }
}
