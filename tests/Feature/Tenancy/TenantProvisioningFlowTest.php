<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Actions\Tenant\CreateTenantAction;
use App\Actions\Tenant\ProvisionTenantAction;
use App\Actions\Tenant\RequestProvisionalPasswordResendAction;
use App\Actions\Tenant\RetryTenantProvisioningAction;
use App\DTOs\Plan\CreatePlanDTO;
use App\DTOs\Plan\PlanPriceDTO;
use App\DTOs\Tenant\TenantDomainDTO;
use App\Enums\BillingCycle;
use App\Enums\DomainPanel;
use App\Enums\ProvisioningStatus;
use App\Enums\UserRole;
use App\Exceptions\Tenant\ProvisionalPasswordException;
use App\Exceptions\Tenant\TenantProvisioningException;
use App\Filament\Tenant\Pages\ChangeProvisionalPassword;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Notifications\ProvisionalPasswordNotification;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\TenantData;
use Tests\TestCase;

/**
 * Fluxo completo com o MySQL de verdade e a fila síncrona: cadastro,
 * provisionamento, primeiro acesso e reenvio. Limpa o que criou no fim.
 */
#[Group('real-database')]
final class TenantProvisioningFlowTest extends TestCase
{
    private string $tenantId;

    private string $databaseName;

    private string $host;

    private ?int $planId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate')->assertSuccessful();

        Notification::fake();

        $this->tenantId = 'prov-'.Str::lower(Str::random(8));
        $this->databaseName = config('tenancy.database.prefix').$this->tenantId.config('tenancy.database.suffix');
        $this->host = "painel.{$this->tenantId}.test";

        $this->planId = app(PlanRepositoryInterface::class)->create(new CreatePlanDTO("Plano {$this->tenantId}", null, true, [
            new PlanPriceDTO(BillingCycle::Monthly, '99.90'),
        ], []))->id;
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        DB::statement("DROP DATABASE IF EXISTS `{$this->databaseName}`");
        DB::table('domains')->where('tenant_id', $this->tenantId)->delete();
        DB::table('tenants')->where('id', $this->tenantId)->delete();

        if ($this->planId !== null) {
            DB::table('plans')->where('id', $this->planId)->delete();
        }

        parent::tearDown();
    }

    public function test_registering_a_tenant_provisions_its_database_and_first_admin(): void
    {
        $tenant = $this->registerTenant();

        $this->assertSame(ProvisioningStatus::Ready, $tenant->provisioning_status);
        $this->assertNotNull($tenant->provisioned_at);
        $this->assertNull($tenant->provisioning_error);
        $this->assertNotEmpty(DB::select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$this->databaseName]));

        $admin = $this->admin($tenant);

        $this->assertSame('Ana Souza', $admin->name);
        $this->assertSame('ana@acme.test', $admin->email);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->must_change_password);

        // Datas do usuário só podem ser lidas dentro do contexto do tenant.
        $secondsLeft = $tenant->run(fn (): float => now()->diffInSeconds(TenantUser::query()->firstOrFail()->password_expires_at));
        $this->assertEqualsWithDelta(24 * 3600, $secondsLeft, 60, 'A senha provisória vale 24 horas.');

        // A senha vai por e-mail e no banco só existe o hash.
        Notification::assertSentTo($admin, ProvisionalPasswordNotification::class, function (ProvisionalPasswordNotification $notification) use ($admin): bool {
            return Hash::check($notification->plainPassword, (string) $admin->getAuthPassword())
                && $notification->plainPassword !== $admin->getAuthPassword()
                && $notification->loginUrl === "http://{$this->host}/admin/login";
        });

        // Provisionar de novo não cria outro admin nem envia outra senha.
        app(ProvisionTenantAction::class)->execute($tenant->id);

        $this->assertSame(1, $tenant->run(fn (): int => TenantUser::query()->count()));
        Notification::assertSentToTimes($admin, ProvisionalPasswordNotification::class, 1);

        $this->expectException(TenantProvisioningException::class);
        app(RetryTenantProvisioningAction::class)->execute($tenant->id);
    }

    public function test_the_first_access_only_reaches_the_password_change_and_then_the_panel(): void
    {
        $tenant = $this->registerTenant();
        $admin = $this->admin($tenant);

        $this->actingAs($admin)
            ->get("http://{$this->host}/admin")
            ->assertRedirect("http://{$this->host}/admin/trocar-senha-provisoria");

        $this->actingAs($admin)
            ->get("http://{$this->host}/admin/trocar-senha-provisoria")
            ->assertOk()
            ->assertSee('Defina sua senha');

        Filament::setCurrentPanel(Filament::getPanel('tenant'));

        Livewire::actingAs($admin)
            ->test(ChangeProvisionalPassword::class)
            ->fillForm(['password' => 'curta', 'password_confirmation' => 'curta'])
            ->call('save')
            ->assertHasFormErrors(['password'])
            ->fillForm(['password' => 'NovaSenha123', 'password_confirmation' => 'NovaSenha123'])
            ->call('save')
            ->assertHasNoFormErrors();

        $admin = $this->admin($tenant);

        $this->assertFalse($admin->must_change_password);
        $this->assertNull($admin->password_expires_at);
        $this->assertTrue(Hash::check('NovaSenha123', (string) $admin->getAuthPassword()));

        $this->flushSession();
        $this->actingAs($admin)->get("http://{$this->host}/admin")->assertOk();

        // Depois do primeiro acesso, a recuperação é pelo próprio painel (RN28).
        $this->post("http://{$this->host}/admin/logout");
        $this->get("http://{$this->host}/admin/password-reset/request")->assertOk();
    }

    public function test_an_expired_provisional_password_ends_the_session(): void
    {
        $tenant = $this->registerTenant();
        $tenant->run(fn (): int => TenantUser::query()->update(['password_expires_at' => now()->subMinute()]));

        $this->actingAs($this->admin($tenant))
            ->get("http://{$this->host}/admin")
            ->assertRedirect("http://{$this->host}/admin/login");

        $this->assertGuest();
    }

    public function test_the_central_resends_the_provisional_password_only_before_the_first_access(): void
    {
        $tenant = $this->registerTenant();
        $before = $this->admin($tenant);

        app(RequestProvisionalPasswordResendAction::class)->execute($tenant->id);

        $after = $this->admin($tenant);

        $this->assertNotSame($before->getAuthPassword(), $after->getAuthPassword(), 'A senha anterior deixa de valer.');
        $this->assertTrue($after->must_change_password);
        Notification::assertSentToTimes($after, ProvisionalPasswordNotification::class, 2);

        // Depois do primeiro acesso, o central não mexe mais na conta.
        $tenant->run(fn (): int => TenantUser::query()->update(['must_change_password' => false, 'password_expires_at' => null]));

        $this->expectExceptionObject(ProvisionalPasswordException::alreadyUsed());
        app(RequestProvisionalPasswordResendAction::class)->execute($tenant->id);
    }

    private function registerTenant(): Tenant
    {
        // A fila é síncrona nos testes: o provisionamento roda dentro do cadastro.
        $tenant = app(CreateTenantAction::class)->execute(TenantData::create(
            slug: $this->tenantId,
            planId: (int) $this->planId,
            domains: [new TenantDomainDTO($this->host, DomainPanel::Admin)],
            company: TenantData::company(TenantData::VALID_ALPHANUMERIC_CNPJ),
        ));

        return $tenant->refresh();
    }

    private function admin(Tenant $tenant): TenantUser
    {
        return $tenant->run(fn (): TenantUser => TenantUser::query()->firstOrFail());
    }
}
