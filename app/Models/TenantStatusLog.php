<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantStatus;
use App\Enums\TenantStatusSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Uma mudança de situação de um tenant. Só se acrescenta; nunca se altera.
 *
 * @property int $id
 * @property string $tenant_id
 * @property TenantStatus $from_status
 * @property TenantStatus $to_status
 * @property TenantStatusSource $source
 * @property int|null $central_user_id
 * @property string|null $reason
 * @property Carbon|null $locked_until
 * @property Carbon|null $created_at
 * @property-read User|null $centralUser
 */
final class TenantStatusLog extends Model
{
    use CentralConnection;

    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'from_status',
        'to_status',
        'source',
        'central_user_id',
        'reason',
        'locked_until',
    ];

    /** @return BelongsTo<User, $this> */
    public function centralUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'central_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'from_status' => TenantStatus::class,
            'to_status' => TenantStatus::class,
            'source' => TenantStatusSource::class,
            'locked_until' => 'datetime',
        ];
    }
}
