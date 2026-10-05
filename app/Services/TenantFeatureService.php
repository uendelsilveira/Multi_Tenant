<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Feature\FeatureNotInPlanException;
use App\Models\Feature;
use App\Models\Tenant;
use App\Repositories\Contracts\FeatureRepositoryInterface;
use App\Repositories\Contracts\FeatureSettingRepositoryInterface;

/**
 * Funcionalidades de um tenant. Uma funcionalidade está ativa quando está no
 * plano do tenant e o admin a ligou (RN04). Tudo aqui roda no contexto do tenant.
 *
 * Não usa cache: o plano é relido a cada requisição, e por isso a troca de
 * plano vale na hora (RN07). Dentro de uma mesma requisição o resultado é
 * reaproveitado, já que o serviço é registrado como `scoped`.
 */
final class TenantFeatureService
{
    /** @var array<string, list<Feature>> */
    private array $planFeatures = [];

    /** @var array<string, list<string>> */
    private array $enabledKeys = [];

    public function __construct(
        private readonly FeatureRepositoryInterface $features,
        private readonly FeatureSettingRepositoryInterface $settings,
    ) {}

    /**
     * Funcionalidades que o plano do tenant inclui. As demais não existem para ele.
     *
     * @return list<Feature>
     */
    public function inPlan(Tenant $tenant): array
    {
        if ($tenant->plan_id === null) {
            return [];
        }

        return $this->planFeatures[$tenant->id.':'.$tenant->plan_id] ??= $this->features->forPlan($tenant->plan_id);
    }

    /**
     * Chaves ativas: no plano e ligadas pelo admin.
     *
     * @return list<string>
     */
    public function activeKeys(Tenant $tenant): array
    {
        $inPlan = array_map(fn (Feature $feature): string => $feature->key, $this->inPlan($tenant));

        if ($inPlan === []) {
            return [];
        }

        $enabled = $this->enabledKeys[$tenant->id] ??= $this->settings->enabledKeys();

        return array_values(array_intersect($inPlan, $enabled));
    }

    public function isActive(Tenant $tenant, string $featureKey): bool
    {
        return in_array($featureKey, $this->activeKeys($tenant), true);
    }

    /** Atalho para rotas, telas e jobs: responde pelo tenant da requisição atual. */
    public function isActiveForCurrentTenant(string $featureKey): bool
    {
        $tenant = tenant();

        return $tenant instanceof Tenant && $this->isActive($tenant, $featureKey);
    }

    /** Só se liga ou desliga o que está no plano. A escolha fica guardada mesmo que o plano mude depois (RN05). */
    public function toggle(Tenant $tenant, string $featureKey, bool $enabled): void
    {
        $inPlan = array_map(fn (Feature $feature): string => $feature->key, $this->inPlan($tenant));

        if (! in_array($featureKey, $inPlan, true)) {
            throw FeatureNotInPlanException::forKey($featureKey);
        }

        $this->settings->set($featureKey, $enabled);

        unset($this->enabledKeys[$tenant->id]);
    }
}
