<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Contracts\DnsLookupInterface;
use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\Enums\BillingCycle;
use App\Enums\UserRole;
use App\Filament\Resources\Domains\Pages\ListDomains;
use App\Models\Domain;
use App\Models\User;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\TenantData;
use Tests\TestCase;

final class DomainResourceTest extends TestCase
{
    use RefreshDatabase;

    private Domain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $planId = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []))->id;

        $tenant = app(TenantRepositoryInterface::class)->create(TenantData::create(planId: $planId));

        $this->domain = $tenant->domains->firstOrFail();
    }

    public function test_an_admin_verifies_a_pending_domain_and_the_author_is_recorded(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        Livewire::test(ListDomains::class)
            ->assertCanSeeTableRecords([$this->domain])
            ->callTableAction('verify', $this->domain)
            ->assertNotified();

        $this->assertDatabaseHas('domains', [
            'id' => $this->domain->id,
            'status' => 'active',
            'verified_by' => $admin->id,
        ]);
        $this->assertNotNull($this->domain->refresh()->verified_at);

        // Verificado, o botão some: não se verifica duas vezes.
        Livewire::test(ListDomains::class)->assertTableActionHidden('verify', $this->domain);
    }

    public function test_the_dns_check_shows_where_the_domain_points_without_changing_it(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $this->app->instance(DnsLookupInterface::class, new class implements DnsLookupInterface
        {
            public function lookup(string $host): array
            {
                return ["A 203.0.113.10 ({$host})"];
            }
        });

        Livewire::test(ListDomains::class)
            ->callTableAction('checkDns', $this->domain)
            ->assertNotified();

        $this->assertDatabaseHas('domains', ['id' => $this->domain->id, 'status' => 'pending']);
    }

    public function test_an_operator_sees_the_domains_but_cannot_verify(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Operator]));

        Livewire::test(ListDomains::class)
            ->assertCanSeeTableRecords([$this->domain])
            ->assertTableActionHidden('verify', $this->domain);
    }
}
