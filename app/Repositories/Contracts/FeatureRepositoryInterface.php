<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface FeatureRepositoryInterface
{
    /**
     * Cria ou atualiza as funcionalidades pela chave. Nunca remove.
     *
     * @param  list<array{key: string, name: string, module: string|null}>  $catalog
     */
    public function upsertCatalog(array $catalog): int;

    /** @return array<int, string> */
    public function options(): array;
}
