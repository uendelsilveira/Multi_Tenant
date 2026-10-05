<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Feature;
use App\Repositories\Contracts\FeatureRepositoryInterface;

final class FeatureRepository implements FeatureRepositoryInterface
{
    public function upsertCatalog(array $catalog): int
    {
        foreach ($catalog as $feature) {
            Feature::query()->updateOrCreate(
                ['key' => $feature['key']],
                ['name' => $feature['name'], 'module' => $feature['module']],
            );
        }

        return count($catalog);
    }

    public function options(): array
    {
        $options = [];

        foreach (Feature::query()->orderBy('module')->orderBy('name')->get(['id', 'name']) as $feature) {
            $options[$feature->id] = $feature->name;
        }

        return $options;
    }

    public function forPlan(int $planId): array
    {
        return array_values(Feature::query()
            ->whereHas('plans', fn ($query) => $query->whereKey($planId))
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->all());
    }
}
