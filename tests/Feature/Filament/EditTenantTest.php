<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\Enums\BillingCycle;
use App\Enums\UserRole;
use App\Filament\Resources\Tenants\Pages\EditTenant;
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

final class EditTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Impede a criação real do banco do tenant durante os testes.
        Event::fake([TenantCreated::class]);

        $planId = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO('Profissional', null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
            new PlanPriceDTO(BillingCycle::Annual, '999.00'),
        ], []))->id;

        app(TenantRepositoryInterface::class)->create(TenantData::create(planId: $planId));

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
    }

    public function test_an_admin_updates_company_data_cycle_and_domains(): void
    {
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(EditTenant::class, ['record' => 'acme'])
            ->assertFormSet(['legal_name' => 'Acme Ltda', 'billing_cycle' => 'monthly'])
            ->fillForm([
                'legal_name' => 'Acme S.A.',
                'billing_cycle' => 'annual',
                'domains' => [
                    ['domain' => 'painel.acme.test', 'panel' => 'admin'],
                    ['domain' => 'app.acme.test', 'panel' => 'user'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $this->assertDatabaseHas('tenants', ['id' => 'acme', 'legal_name' => 'Acme S.A.', 'billing_cycle' => 'annual']);
        $this->assertDatabaseHas('domains', ['tenant_id' => 'acme', 'domain' => 'painel.acme.test', 'panel' => 'admin']);
        $this->assertDatabaseHas('domains', ['tenant_id' => 'acme', 'domain' => 'app.acme.test', 'panel' => 'user', 'status' => 'pending']);
    }

    public function test_removing_the_only_admin_panel_domain_is_refused(): void
    {
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(EditTenant::class, ['record' => 'acme'])
            ->fillForm(['domains' => [['domain' => 'portal.acme.test', 'panel' => 'customer']]])
            ->call('save')
            ->assertNotified();

        $undoRepeaterFake();

        $this->assertDatabaseHas('domains', ['tenant_id' => 'acme', 'domain' => 'painel.acme.test', 'panel' => 'admin']);
        $this->assertDatabaseMissing('domains', ['domain' => 'portal.acme.test']);
    }

    public function test_the_slug_cannot_be_changed(): void
    {
        Livewire::test(EditTenant::class, ['record' => 'acme'])
            ->assertFormFieldDisabled('id');
    }
}
