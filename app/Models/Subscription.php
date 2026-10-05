<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentGateway;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Cobrança recorrente de um tenant em um gateway.
 *
 * @property int $id
 * @property string $tenant_id
 * @property PaymentGateway $gateway
 * @property string|null $gateway_customer_id
 * @property string|null $gateway_subscription_id
 * @property SubscriptionStatus $status
 * @property Carbon|null $overdue_since
 * @property Carbon|null $last_paid_at
 * @property string|null $last_error
 * @property-read Tenant|null $tenant
 */
final class Subscription extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'gateway',
        'gateway_customer_id',
        'gateway_subscription_id',
        'status',
        'overdue_since',
        'last_paid_at',
        'last_error',
    ];

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'gateway' => PaymentGateway::class,
            'status' => SubscriptionStatus::class,
            'overdue_since' => 'datetime',
            'last_paid_at' => 'datetime',
        ];
    }
}
