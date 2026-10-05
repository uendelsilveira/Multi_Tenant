<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Actions\Tenant\ChangeTenantStatusAction;
use App\Actions\Tenant\CreateTenantAction;
use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\DTOs\Tenant\ChangeTenantStatusDTO;
use App\DTOs\Tenant\TenantDomainDTO;
use App\Enums\BillingCycle;
use App\Enums\DomainPanel;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\SuspensionGatedJob;
use Tests\Support\TenantData;
use Tests\TestCase;

/**
 * Suspensão vista de dentro do tenant, com o MySQL de verdade: quem estava
 * usando é bloqueado, e jobs de módulo não executam.
 */
#[Group('real-database')]
final class SuspendedTenantTest extends TestCase
{
    private string $tenantId;

    private string $databaseName;

    private string $adminHost;

    private ?int $planId = null;

    private ?int $centralUserId = null;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate')->assertSuccessful();

        Notification::fake();
        SuspensionGatedJob::$handled = 0;

        $this->tenantId = 'susp-'.Str::lower(Str::random(8));
        $this->databaseName = config('tenancy.database.prefix').$this->tenantId.config('tenancy.database.suffix');
        $this->adminHost = "painel.{$this->tenantId}.test";

        $this->planId = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO("Plano {$this->tenantId}", null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []))->id;

        $this->centralUserId = User::factory()->create(['role' => UserRole::Admin])->id;

        // A fila é síncrona nos testes: o provisionamento roda dentro do cadastro.
        $this->tenant = app(CreateTenantAction::class)->execute(TenantData::create(
            slug: $this->tenantId,
            planId: (int) $this->planId,
            domains: [new TenantDomainDTO($this->adminHost, DomainPanel::Admin)],
            company: TenantData::company(TenantData::VALID_ALPHANUMERIC_CNPJ),
        ))->refresh();

        DB::table('domains')->where('tenant_id', $this->tenantId)->update(['status' => 'active', 'verified_at' => now()]);
        $this->tenant->run(fn (): int => TenantUser::query()->update(['must_change_password' => false, 'password_expires_at' => null]));
    }

    protected function tearDown(): void
    {
        $this->leaveTenant();

        DB::statement("DROP DATABASE IF EXISTS `{$this->databaseName}`");
        DB::table('tenant_status_logs')->where('tenant_id', $this->tenantId)->delete();
        DB::table('domains')->where('tenant_id', $this->tenantId)->delete();
        DB::table('subscriptions')->where('tenant_id', $this->tenantId)->delete();
        DB::table('tenants')->where('id', $this->tenantId)->delete();
        DB::table('plans')->where('id', $this->planId)->delete();
        DB::table('users')->where('id', $this->centralUserId)->delete();

        parent::tearDown();
    }

    public function test_whoever_was_using_the_tenant_is_blocked_when_it_is_suspended_and_returns_when_reactivated(): void
    {
        $admin = $this->tenant->run(fn (): TenantUser => TenantUser::query()->firstOrFail());

        $this->visit($admin, '/admin')->assertOk();

        $this->changeStatus(TenantStatus::Suspended, 'Inadimplência.');

        $this->visit($admin, '/admin')
            ->assertForbidden()
            ->assertSee('Entre em contato com o administrador');
        $this->visit($admin, '/admin/pessoas')->assertForbidden();

        $this->changeStatus(TenantStatus::Active, 'Pagamento regularizado.');

        $this->visit($admin, '/admin')->assertOk();

        $this->assertSame(
            ['active → suspended', 'suspended → active'],
            DB::table('tenant_status_logs')->where('tenant_id', $this->tenantId)->orderBy('id')->get()
                ->map(fn (object $log): string => "{$log->from_status} → {$log->to_status}")->all(),
        );
    }

    public function test_module_jobs_do_not_run_while_the_tenant_is_suspended(): void
    {
        tenancy()->initialize($this->tenant);
        SuspensionGatedJob::dispatch();
        $this->assertSame(1, SuspensionGatedJob::$handled);

        $this->leaveTenant();
        $this->changeStatus(TenantStatus::Suspended, 'Inadimplência.');

        // Inclusive um job que já estava na fila: quem decide é a situação na hora de executar.
        tenancy()->initialize($this->tenant);
        SuspensionGatedJob::dispatch();
        $this->assertSame(1, SuspensionGatedJob::$handled);

        $this->leaveTenant();
        $this->changeStatus(TenantStatus::Active, 'Pagamento regularizado.');

        tenancy()->initialize($this->tenant);
        SuspensionGatedJob::dispatch();
        $this->assertSame(2, SuspensionGatedJob::$handled);
    }

    private function changeStatus(TenantStatus $status, string $reason): void
    {
        $this->leaveTenant();

        app(ChangeTenantStatusAction::class)->execute(
            new ChangeTenantStatusDTO($this->tenantId, $status, $reason, null, (int) $this->centralUserId),
        );
    }

    private function visit(TenantUser $person, string $path): TestResponse
    {
        $this->leaveTenant();
        $this->flushSession();

        $response = $this->actingAs($person, 'tenant')->get("http://{$this->adminHost}{$path}");

        $this->leaveTenant();

        return $response;
    }

    private function leaveTenant(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
    }
}
