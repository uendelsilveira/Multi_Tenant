<?php

declare(strict_types=1);

namespace Tests\Feature\Features;

use App\Services\FeatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FeatureCatalogSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_command_syncs_the_declared_catalog(): void
    {
        config(['features.catalog' => [
            ['key' => 'helpdesk.tickets', 'name' => 'Chamados', 'module' => 'helpdesk'],
            ['key' => 'monitoring.uptime', 'name' => 'Monitoramento'],
            ['name' => 'Entrada sem chave é descartada'],
        ]]);

        $this->artisan('features:sync')->assertSuccessful();

        $this->assertDatabaseCount('features', 2);
        $this->assertDatabaseHas('features', ['key' => 'helpdesk.tickets', 'name' => 'Chamados', 'module' => 'helpdesk']);
        $this->assertDatabaseHas('features', ['key' => 'monitoring.uptime', 'module' => null]);
    }

    public function test_syncing_again_updates_by_key_and_never_removes(): void
    {
        config(['features.catalog' => [
            ['key' => 'helpdesk.tickets', 'name' => 'Chamados', 'module' => 'helpdesk'],
            ['key' => 'monitoring.uptime', 'name' => 'Monitoramento'],
        ]]);
        $this->artisan('features:sync')->assertSuccessful();

        config(['features.catalog' => [
            ['key' => 'helpdesk.tickets', 'name' => 'Tickets', 'module' => 'helpdesk'],
        ]]);
        $this->artisan('features:sync')->assertSuccessful();

        $this->assertDatabaseCount('features', 2);
        $this->assertDatabaseHas('features', ['key' => 'helpdesk.tickets', 'name' => 'Tickets']);
        $this->assertEqualsCanonicalizing(
            ['Tickets', 'Monitoramento'],
            array_values(app(FeatureService::class)->options()),
        );
    }
}
