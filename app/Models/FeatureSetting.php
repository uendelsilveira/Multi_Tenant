<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Escolha do admin do tenant sobre uma funcionalidade. Vive no banco do tenant.
 *
 * @property string $feature_key
 * @property bool $enabled
 */
final class FeatureSetting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'feature_key';

    protected $keyType = 'string';

    protected $fillable = [
        'feature_key',
        'enabled',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }
}
