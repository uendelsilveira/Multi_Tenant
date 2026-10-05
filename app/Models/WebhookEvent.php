<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentGateway;
use App\Enums\WebhookOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Um evento recebido de um gateway, gravado antes de qualquer processamento.
 *
 * @property int $id
 * @property PaymentGateway $gateway
 * @property string $gateway_event_id
 * @property string $type
 * @property array<string, mixed> $payload
 * @property string|null $tenant_id
 * @property WebhookOutcome|null $outcome
 * @property Carbon|null $processed_at
 * @property Carbon|null $created_at
 */
final class WebhookEvent extends Model
{
    use CentralConnection;

    public const UPDATED_AT = null;

    protected $fillable = [
        'gateway',
        'gateway_event_id',
        'type',
        'payload',
        'tenant_id',
        'outcome',
        'processed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'gateway' => PaymentGateway::class,
            'outcome' => WebhookOutcome::class,
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
