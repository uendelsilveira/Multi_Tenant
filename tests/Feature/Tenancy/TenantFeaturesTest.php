<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Actions\Tenant\CreateTenantAction;
use App\Actions\Tenant\UpdateTenantAction;
use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\DTOs\Plan\UpdatePlanDTO;
use App\DTOs\Tenant\TenantDomainDTO;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Enums\BillingCycle;
use App\Enums\DomainPanel;
use App\Events\Tenant\TenantPlanChanged;
use App\Filament\Tenant\Admin\Pages\ManageFeatures;
use App\Http\Middleware\EnsureFeatureIsActive;
use App\Http\Middleware\InitializeTenancyForTenantDomain;
use App\Models\Feature;
use App\Models\FeatureSetting;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Services\TenantFeatureService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\FeatureGatedJob;
use Tests\Support\FeatureGatedPage;
use Tests\Support\TenantData;
use Tests\TestCase;

/**
 * Funcionalidades de um tenant (RF05, RF14, RF15, RF20), com o MySQL de verdade:
 * o plano está no banco central e a escolha do admin, no banco do tenant.
 */
#[Group('real-database')]
final class TenantFeaturesTest extends TestCase
{
    private string $tenantId;

    private string $databaseName;

    private string $adminHost;

    private int $fullPlanId;

    private int $basicPlanId;

    /** @var list<int> */
    private array $featureIds = [];

    private Tenant $tenant;

    private TenantUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate')->assertSuccessful();

        Notification::fake();
        FeatureGatedJob::$handled = 0;

        $this->tenantId = 'feat-'.Str::lower(Str::random(8));
        $this->databaseName = config('tenancy.database.prefix').$this->tenantId.config('tenancy.database.suffix');
        $this->adminHost = "painel.{$this->tenantId}.test";

        // Chaves fixas, como as que um módulo declararia; nomes únicos por execução.
        $tickets = Feature::query()->updateOrCreate(['key' => 'helpdesk.tickets'], ['name' => 'Chamados', 'module' => 'Helpdesk']);
        $reports = Feature::query()->updateOrCreate(['key' => 'reports.export'], ['name' => 'Exportação', 'module' => 'Relatórios']);
        $this->featureIds = [$tickets->id, $reports->id];

        $plans = app(PlanRepositoryInterface::class);
        $prices = [new PlanPriceDTO(BillingCycle::Monthly, '99.90')];

        $this->fullPlanId = $plans->create(new CreatePlanDTO("Completo {$this->tenantId}", null, true, $prices, $this->featureIds))->id;
        $this->basicPlanId = $plans->create(new CreatePlanDTO("Básico {$this->tenantId}", null, true, $prices, [$tickets->id]))->id;

        // A fila é síncrona nos testes: o provisionamento roda dentro do cadastro.
        $this->tenant = app(CreateTenantAction::class)->execute(TenantData::create(
            slug: $this->tenantId,
            planId: $this->fullPlanId,
            domains: [new TenantDomainDTO($this->adminHost, DomainPanel::Admin)],
            company: TenantData::company(TenantData::VALID_ALPHANUMERIC_CNPJ),
        ))->refresh();

        DB::table('domains')->where('tenant_id', $this->tenantId)->update(['status' => 'active', 'verified_at' => now()]);

        $this->enterTenant();
        TenantUser::query()->update(['must_change_password' => false, 'password_expires_at' => null]);
        $this->admin = TenantUser::query()->with('role')->firstOrFail();
        $this->actingAs($this->admin, 'tenant');
    }

    protected function tearDown(): void
    {
        $this->leaveTenant();

        DB::statement("DROP DATABASE IF EXISTS `{$this->databaseName}`");
        DB::table('domains')->where('tenant_id', $this->tenantId)->delete();
        DB::table('tenants')->where('id', $this->tenantId)->delete();
        DB::table('plans')->whereIn('id', [$this->fullPlanId, $this->basicPlanId])->delete();
        DB::table('features')->whereIn('id', $this->featureIds)->delete();

        parent::tearDown();
    }

    public function test_features_of_the_plan_are_listed_and_all_start_turned_off(): void
    {
        Livewire::test(ManageFeatures::class)
            ->assertSuccessful()
            ->assertSee('Helpdesk · Chamados')
            ->assertSee('Relatórios · Exportação')
            ->assertFormSet(['enabled' => []]);

        $this->assertSame([], $this->features()->activeKeys($this->tenant));
        $this->assertFalse($this->features()->isActive($this->tenant, 'helpdesk.tickets'));
    }

    public function test_the_admin_turns_features_on_and_off_and_nothing_is_deleted(): void
    {
        Livewire::test(ManageFeatures::class)
            ->fillForm(['enabled' => ['helpdesk.tickets', 'reports.export']])
            ->call('save')
            ->assertNotified();

        $this->fresh();
        $this->assertEqualsCanonicalizing(['helpdesk.tickets', 'reports.export'], $this->features()->activeKeys($this->tenant));

        Livewire::test(ManageFeatures::class)
            ->assertFormSet(fn (array $state): bool => count($state['enabled']) === 2)
            ->fillForm(['enabled' => ['helpdesk.tickets']])
            ->call('save');

        $this->fresh();
        $this->assertSame(['helpdesk.tickets'], $this->features()->activeKeys($this->tenant));

        // Desligar guarda a escolha como desligada; a linha continua lá.
        $this->assertFalse(FeatureSetting::query()->findOrFail('reports.export')->enabled);
    }

    public function test_a_downgrade_turns_off_at_once_what_left_the_plan_and_an_upgrade_brings_the_choice_back(): void
    {
        $this->turnOn('helpdesk.tickets', 'reports.export');

        Event::fake([TenantPlanChanged::class]);

        $this->changePlan($this->basicPlanId);

        Event::assertDispatched(TenantPlanChanged::class, fn (TenantPlanChanged $event): bool => $event->tenantId === $this->tenantId
            && $event->fromPlanId === $this->fullPlanId
            && $event->toPlanId === $this->basicPlanId);

        $this->assertSame(['helpdesk.tickets'], $this->features()->activeKeys($this->tenant));
        $this->assertFalse($this->features()->isActive($this->tenant, 'reports.export'));
        $this->assertTrue(FeatureSetting::query()->findOrFail('reports.export')->enabled, 'A escolha do admin é preservada.');

        // A funcionalidade que saiu do plano some da tela do admin.
        Livewire::test(ManageFeatures::class)
            ->assertSee('Helpdesk · Chamados')
            ->assertDontSee('Relatórios · Exportação');

        $this->changePlan($this->fullPlanId);

        $this->assertTrue($this->features()->isActive($this->tenant, 'reports.export'), 'De volta ao plano maior, volta ligada como estava.');
    }

    public function test_editing_the_features_of_a_plan_reaches_its_tenants_at_once(): void
    {
        $this->turnOn('helpdesk.tickets', 'reports.export');
        $this->leaveTenant();

        $plans = app(PlanRepositoryInterface::class);
        $plan = $plans->find($this->fullPlanId);
        $this->assertNotNull($plan);

        // O central tira a exportação do plano completo.
        $plans->update($plan, new UpdatePlanDTO($plan->id, $plan->name, null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], [$this->featureIds[0]]));

        $this->enterTenant();
        $this->fresh();

        $this->assertSame(['helpdesk.tickets'], $this->features()->activeKeys($this->tenant));
    }

    public function test_routes_screens_and_jobs_of_an_inactive_feature_do_not_run(): void
    {
        Route::middleware(['web', InitializeTenancyForTenantDomain::class, EnsureFeatureIsActive::class.':helpdesk.tickets'])
            ->get('/modulo-de-teste/chamados', fn (): string => 'chamados');

        // Desligada: a rota não existe, a tela não abre e o job não executa.
        $this->visit('/modulo-de-teste/chamados')->assertNotFound();

        $this->enterTenant();
        $this->assertFalse(FeatureGatedPage::canAccess());
        FeatureGatedJob::dispatch();
        $this->assertSame(0, FeatureGatedJob::$handled);

        // Ligada: tudo passa a responder.
        $this->turnOn('helpdesk.tickets');

        $this->visit('/modulo-de-teste/chamados')->assertOk()->assertSee('chamados');

        $this->enterTenant();
        $this->fresh();
        $this->assertTrue(FeatureGatedPage::canAccess());
        FeatureGatedJob::dispatch();
        $this->assertSame(1, FeatureGatedJob::$handled);

        // Um job que ficou na fila e só roda depois do downgrade também desiste.
        $this->changePlan($this->basicPlanId);
        $this->changePlan($this->fullPlanId);
        $this->turnOff('helpdesk.tickets');
        FeatureGatedJob::dispatch();
        $this->assertSame(1, FeatureGatedJob::$handled);
    }

    public function test_only_who_may_manage_features_reaches_the_screen(): void
    {
        $limited = Role::query()->create(['name' => 'Gestor de perfis', 'base_type' => 'admin', 'permissions' => ['roles.manage']]);
        $carla = TenantUser::query()->create(['name' => 'Carla Dias', 'email' => 'carla@acme.test', 'password' => 'SenhaDeTeste123', 'role_id' => $limited->id])->load('role');

        $this->flushSession();
        $this->actingAs($carla, 'tenant');

        $this->assertFalse(ManageFeatures::canAccess());
        Livewire::test(ManageFeatures::class)->assertForbidden();
    }

    private function features(): TenantFeatureService
    {
        return app(TenantFeatureService::class);
    }

    /** Descarta o que o serviço guardou na "requisição" anterior, como acontece entre requisições de verdade. */
    private function fresh(): void
    {
        app()->forgetScopedInstances();
        $this->tenant->refresh();
    }

    private function turnOn(string ...$keys): void
    {
        $this->enterTenant();

        foreach ($keys as $key) {
            $this->features()->toggle($this->tenant, $key, true);
        }

        $this->fresh();
    }

    private function turnOff(string $key): void
    {
        $this->enterTenant();
        $this->features()->toggle($this->tenant, $key, false);
        $this->fresh();
    }

    /** Troca de plano feita pelo central, como na edição do tenant. */
    private function changePlan(int $planId): void
    {
        $this->leaveTenant();

        app(UpdateTenantAction::class)->execute(new UpdateTenantDTO(
            tenantId: $this->tenantId,
            company: TenantData::company(TenantData::VALID_ALPHANUMERIC_CNPJ),
            planId: $planId,
            billingCycle: BillingCycle::Monthly,
            domains: [new TenantDomainDTO($this->adminHost, DomainPanel::Admin)],
        ));

        $this->enterTenant();
        $this->fresh();
    }

    private function visit(string $path): TestResponse
    {
        $this->leaveTenant();
        app()->forgetScopedInstances();

        $response = $this->get("http://{$this->adminHost}{$path}");

        $this->leaveTenant();

        return $response;
    }

    private function enterTenant(): void
    {
        if (! tenancy()->initialized) {
            tenancy()->initialize($this->tenant);
        }

        Filament::setCurrentPanel(Filament::getPanel(DomainPanel::Admin->panelId()));
    }

    private function leaveTenant(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
    }
}
