<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\Enums\BillingCycle;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantData;
use Tests\TestCase;

final class UnprovisionedTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // A requisição deixa a tenancy inicializada; volta ao contexto central antes do rollback.
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_a_tenant_that_is_not_ready_shows_the_waiting_page(): void
    {
        $planId = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []))->id;

        // Cadastrado, mas sem provisionamento: o banco dele ainda não existe.
        app(TenantRepositoryInterface::class)->create(TenantData::create(planId: $planId));

        $this->get('http://painel.acme.test/admin/login')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '30')
            ->assertSee('Ambiente em preparação');
    }

    public function test_an_unknown_domain_is_not_found(): void
    {
        $this->get('http://ninguem.exemplo.test/admin/login')->assertNotFound();
    }
}
