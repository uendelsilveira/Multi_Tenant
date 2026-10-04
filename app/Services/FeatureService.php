<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\FeatureRepositoryInterface;

final class FeatureService
{
    public function __construct(
        private readonly FeatureRepositoryInterface $repository,
    ) {}

    /**
     * Sincroniza o catálogo declarado em config/features.php.
     * Entradas sem chave ou nome são descartadas; chave repetida vale a última.
     *
     * @param  array<int|string, mixed>  $catalog
     */
    public function syncCatalog(array $catalog): int
    {
        $normalized = [];

        foreach ($catalog as $feature) {
            if (! is_array($feature) || empty($feature['key']) || empty($feature['name'])) {
                continue;
            }

            $key = (string) $feature['key'];

            $normalized[$key] = [
                'key' => $key,
                'name' => (string) $feature['name'],
                'module' => isset($feature['module']) ? (string) $feature['module'] : null,
            ];
        }

        return $this->repository->upsertCatalog(array_values($normalized));
    }

    /** @return array<int, string> */
    public function options(): array
    {
        return $this->repository->options();
    }
}
