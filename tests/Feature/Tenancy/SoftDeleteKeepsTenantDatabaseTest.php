<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Actions\Tenant\RestoreTenantAction;
use App\Actions\Tenant\SoftDeleteTenantAction;
use App\Contracts\TenantEnvironmentInterface;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Usa o MySQL de verdade, sem transação: cria o banco de um tenant, exclui o
 * tenant e confere que o banco continua lá (RN24). Limpa o que criou no fim.
 */
#[Group('real-database')]
final class SoftDeleteKeepsTenantDatabaseTest extends TestCase
{
    private ?string $tenantId = null;

    private ?string $databaseName = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate')->assertSuccessful();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        if ($this->databaseName !== null) {
            DB::statement("DROP DATABASE IF EXISTS `{$this->databaseName}`");
        }

        if ($this->tenantId !== null) {
            DB::table('domains')->where('tenant_id', $this->tenantId)->delete();
            DB::table('tenants')->where('id', $this->tenantId)->delete();
        }

        parent::tearDown();
    }

    public function test_soft_deleting_a_tenant_keeps_its_database(): void
    {
        $this->tenantId = 'softdel-'.Str::lower(Str::random(8));
        $this->databaseName = config('tenancy.database.prefix').$this->tenantId.config('tenancy.database.suffix');

        $tenant = Tenant::query()->create(['id' => $this->tenantId, 'legal_name' => 'Teste de exclusão lógica']);
        app(TenantEnvironmentInterface::class)->ensureDatabaseExists($tenant);

        $this->assertSame($this->databaseName, $tenant->database()->getName());
        $this->assertTrue($this->databaseExists(), 'O banco do tenant deveria ter sido criado.');

        app(SoftDeleteTenantAction::class)->execute($tenant->id);

        $this->assertNull(Tenant::query()->find($this->tenantId));
        $this->assertNotNull(Tenant::withTrashed()->find($this->tenantId));
        $this->assertTrue($this->databaseExists(), 'A exclusão lógica não pode apagar o banco do tenant.');

        app(RestoreTenantAction::class)->execute($tenant->id);

        $this->assertNotNull(Tenant::query()->find($this->tenantId));
        $this->assertTrue($this->databaseExists());
    }

    private function databaseExists(): bool
    {
        return DB::select(
            'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$this->databaseName],
        ) !== [];
    }
}
