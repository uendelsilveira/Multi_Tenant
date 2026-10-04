<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $module
 */
final class Feature extends Model
{
    use CentralConnection;

    protected $fillable = [
        'key',
        'name',
        'module',
    ];

    /** @return BelongsToMany<Plan, $this> */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }
}
