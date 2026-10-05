<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\Enums\BillingCycle;
use App\Enums\UserRole;
use App\Events\Tenant\TenantRegistered;
use App\Filament\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Resources\Tenants\Pages\EditTenant;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Stancl\Tenancy\Events\TenantCreated;
use Tests\Support\TenantData;
use Tests\TestCase;

final class TenantResourceTest extends TestCase
{
    use RefreshDatabase;

    private int $planId;

    protected function setUp(): void
    {
        parent::setUp();

        // Impede a criação real do banco do tenant durante os testes.
        Event::fake([TenantCreated::class, TenantRegistered::class]);

        $this->planId = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []))->id;
    }

    public function test_an_admin_registers_a_tenant_with_its_domains(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreateTenant::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        // Os campos chegam ao banco normalizados: documento e CEP sem máscara, UF em maiúsculas, host em minúsculas.
        $this->assertDatabaseHas('tenants', [
            'id' => 'acme',
            'legal_name' => 'Acme Ltda',
            'document' => '11222333000181',
            'contact_phone' => '51999990000',
            'zip_code' => '90000000',
            'state' => 'RS',
            'plan_id' => $this->planId,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('domains', ['tenant_id' => 'acme', 'domain' => 'painel.acme.test', 'panel' => 'admin', 'status' => 'pending']);
        $this->assertDatabaseHas('domains', ['tenant_id' => 'acme', 'domain' => 'portal.acme.test', 'panel' => 'customer']);

        Event::assertDispatched(TenantRegistered::class);
    }

    public function test_a_tenant_without_an_admin_panel_domain_is_refused(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreateTenant::class)
            ->fillForm($this->formData(['domains' => [['domain' => 'portal.acme.test', 'panel' => 'customer']]]))
            ->call('create')
            ->assertNotified();

        $undoRepeaterFake();

        $this->assertDatabaseCount('tenants', 0);
        Event::assertNotDispatched(TenantRegistered::class);
    }

    public function test_the_slug_format_is_validated_in_the_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(CreateTenant::class)
            ->fillForm($this->formData(['id' => 'Acme Ltda']))
            ->call('create')
            ->assertHasFormErrors(['id']);

        $undoRepeaterFake();

        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_deleting_a_tenant_is_a_soft_delete_and_it_can_be_restored(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $tenant = app(TenantRepositoryInterface::class)->create(TenantData::create(planId: $this->planId));

        Livewire::test(ListTenants::class)->callTableAction('delete', $tenant);

        $this->assertSoftDeleted('tenants', ['id' => 'acme']);
        $this->assertDatabaseHas('domains', ['tenant_id' => 'acme']);

        Livewire::test(EditTenant::class, ['record' => 'acme'])->callAction('restore');

        $this->assertNotNull(Tenant::query()->find('acme'));
    }

    public function test_an_operator_can_list_but_not_register_or_delete_tenants(): void
    {
        $tenant = app(TenantRepositoryInterface::class)->create(TenantData::create(planId: $this->planId));
        $this->actingAs(User::factory()->create(['role' => UserRole::Operator]));

        Livewire::test(ListTenants::class)
            ->assertSuccessful()
            ->assertTableActionHidden('delete', $tenant);
        Livewire::test(CreateTenant::class)->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function formData(array $overrides = []): array
    {
        return [
            'id' => 'acme',
            'person_type' => 'pj',
            'legal_name' => 'Acme Ltda',
            'trade_name' => 'Acme',
            'document' => '11.222.333/0001-81',
            'contact_name' => 'Ana Souza',
            'contact_email' => 'ana@acme.test',
            'contact_phone' => '(51) 99999-0000',
            'zip_code' => '90000-000',
            'street' => 'Rua A',
            'number' => '10',
            'district' => 'Centro',
            'city' => 'Porto Alegre',
            'state' => 'rs',
            'plan_id' => $this->planId,
            'billing_cycle' => 'monthly',
            'billing_gateway' => 'asaas',
            'domains' => [
                ['domain' => 'Painel.Acme.test', 'panel' => 'admin'],
                ['domain' => 'portal.acme.test', 'panel' => 'customer'],
            ],
            ...$overrides,
        ];
    }
}
