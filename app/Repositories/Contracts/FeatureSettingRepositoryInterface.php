<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Opera no banco do tenant: só pode ser usado dentro do contexto de um tenant.
 */
interface FeatureSettingRepositoryInterface
{
    /**
     * Chaves que o admin do tenant deixou ligadas.
     *
     * @return list<string>
     */
    public function enabledKeys(): array;

    public function set(string $featureKey, bool $enabled): void;
}
