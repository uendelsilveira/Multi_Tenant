<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\FeatureSetting;
use App\Repositories\Contracts\FeatureSettingRepositoryInterface;

final class FeatureSettingRepository implements FeatureSettingRepositoryInterface
{
    public function enabledKeys(): array
    {
        $keys = FeatureSetting::query()->where('enabled', true)->toBase()->pluck('feature_key');

        return array_values(array_map(strval(...), $keys->all()));
    }

    public function set(string $featureKey, bool $enabled): void
    {
        FeatureSetting::query()->updateOrCreate(
            ['feature_key' => $featureKey],
            ['enabled' => $enabled],
        );
    }
}
