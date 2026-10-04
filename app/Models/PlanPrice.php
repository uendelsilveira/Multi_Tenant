<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingCycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * @property int $id
 * @property int $plan_id
 * @property BillingCycle $billing_cycle
 * @property string $price
 */
final class PlanPrice extends Model
{
    use CentralConnection;

    public $timestamps = false;

    protected $fillable = [
        'plan_id',
        'billing_cycle',
        'price',
    ];

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'price' => 'decimal:2',
        ];
    }
}
