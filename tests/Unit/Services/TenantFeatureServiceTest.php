<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\Feature\FeatureNotInPlanException;
use App\Models\Feature;
use App\Models\Tenant;
use App\Repositories\Contracts\FeatureRepositoryInterface;
use App\Repositories\Contracts\FeatureSettingRepositoryInterface;
use App\Services\TenantFeatureService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class TenantFeatureServiceTest extends TestCase
{
    private FeatureRepositoryInterface&MockInterface $features;

    private FeatureSettingRepositoryInterface&MockInterface $settings;

    private TenantFeatureService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->features = Mockery::mock(FeatureRepositoryInterface::class);
        $this->settings = Mockery::mock(FeatureSettingRepositoryInterface::class);
        $this->service = new TenantFeatureService($this->features, $this->settings);

        // Plano 1 inclui chamados e relatórios. Plano 2, só chamados.
        $this->features->shouldReceive('forPlan')->with(1)->andReturn([$this->feature('helpdesk.tickets'), $this->feature('reports.export')])->byDefault();
        $this->features->shouldReceive('forPlan')->with(2)->andReturn([$this->feature('helpdesk.tickets')])->byDefault();
    }

    public function test_a_feature_in_the_plan_is_off_until_the_admin_turns_it_on(): void
    {
        $this->settings->shouldReceive('enabledKeys')->andReturn([]);

        $this->assertFalse($this->service->isActive($this->tenant(1), 'helpdesk.tickets'));
        $this->assertSame([], $this->service->activeKeys($this->tenant(1)));
    }

    public function test_a_feature_is_active_when_it_is_in_the_plan_and_turned_on(): void
    {
        $this->settings->shouldReceive('enabledKeys')->andReturn(['helpdesk.tickets']);

        $this->assertTrue($this->service->isActive($this->tenant(1), 'helpdesk.tickets'));
        $this->assertFalse($this->service->isActive($this->tenant(1), 'reports.export'));
    }

    public function test_turned_on_is_not_enough_when_the_plan_does_not_include_it(): void
    {
        // A escolha continua guardada de quando o tenant estava em um plano maior.
        $this->settings->shouldReceive('enabledKeys')->andReturn(['helpdesk.tickets', 'reports.export']);

        $this->assertSame(['helpdesk.tickets'], $this->service->activeKeys($this->tenant(2)));
        $this->assertFalse($this->service->isActive($this->tenant(2), 'reports.export'));
    }

    public function test_changing_plan_takes_effect_on_the_next_question_even_in_the_same_process(): void
    {
        $this->settings->shouldReceive('enabledKeys')->andReturn(['helpdesk.tickets', 'reports.export']);

        $tenant = $this->tenant(1);
        $this->assertTrue($this->service->isActive($tenant, 'reports.export'));

        $tenant->plan_id = 2;
        $this->assertFalse($this->service->isActive($tenant, 'reports.export'));

        $tenant->plan_id = 1;
        $this->assertTrue($this->service->isActive($tenant, 'reports.export'), 'De volta ao plano maior, a escolha anterior reaparece.');
    }

    public function test_a_tenant_without_plan_has_no_features_and_nothing_is_read(): void
    {
        $this->features->shouldNotReceive('forPlan');
        $this->settings->shouldNotReceive('enabledKeys');

        $this->assertSame([], $this->service->inPlan($this->tenant(null)));
        $this->assertFalse($this->service->isActive($this->tenant(null), 'helpdesk.tickets'));
    }

    public function test_the_plan_is_read_once_per_request(): void
    {
        $this->features->shouldReceive('forPlan')->once()->with(1)->andReturn([$this->feature('helpdesk.tickets')]);
        $this->settings->shouldReceive('enabledKeys')->once()->andReturn(['helpdesk.tickets']);

        $tenant = $this->tenant(1);

        $this->service->isActive($tenant, 'helpdesk.tickets');
        $this->service->isActive($tenant, 'reports.export');
        $this->service->inPlan($tenant);
    }

    public function test_turning_on_stores_the_choice_and_is_seen_right_away(): void
    {
        $this->settings->shouldReceive('enabledKeys')->twice()->andReturn([], ['reports.export']);
        $this->settings->shouldReceive('set')->once()->with('reports.export', true);

        $tenant = $this->tenant(1);

        $this->assertFalse($this->service->isActive($tenant, 'reports.export'));

        $this->service->toggle($tenant, 'reports.export', true);

        $this->assertTrue($this->service->isActive($tenant, 'reports.export'));
    }

    public function test_a_feature_outside_the_plan_cannot_be_turned_on(): void
    {
        $this->settings->shouldNotReceive('set');

        $this->expectExceptionObject(FeatureNotInPlanException::forKey('reports.export'));

        $this->service->toggle($this->tenant(2), 'reports.export', true);
    }

    public function test_outside_a_tenant_nothing_is_active(): void
    {
        $this->assertFalse($this->service->isActiveForCurrentTenant('helpdesk.tickets'));
    }

    private function tenant(?int $planId): Tenant
    {
        $tenant = new Tenant;
        $tenant->id = 'acme';
        $tenant->plan_id = $planId;

        return $tenant;
    }

    private function feature(string $key): Feature
    {
        return new Feature(['key' => $key, 'name' => $key, 'module' => null]);
    }
}
