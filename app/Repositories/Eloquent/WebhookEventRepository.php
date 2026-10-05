<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\PaymentGateway;
use App\Enums\WebhookOutcome;
use App\Models\WebhookEvent;
use App\Repositories\Contracts\WebhookEventRepositoryInterface;
use Illuminate\Database\UniqueConstraintViolationException;

final class WebhookEventRepository implements WebhookEventRepositoryInterface
{
    public function storeOnce(PaymentGateway $gateway, string $eventId, string $type, array $payload): ?WebhookEvent
    {
        try {
            return WebhookEvent::query()->create([
                'gateway' => $gateway->value,
                'gateway_event_id' => $eventId,
                'type' => $type,
                'payload' => $payload,
            ]);
        } catch (UniqueConstraintViolationException) {
            // O índice único é quem decide, e isso vale mesmo com duas entregas simultâneas.
            return null;
        }
    }

    public function find(int $id): ?WebhookEvent
    {
        return WebhookEvent::query()->find($id);
    }

    public function markProcessed(WebhookEvent $event, WebhookOutcome $outcome, ?string $tenantId): void
    {
        $event->update([
            'outcome' => $outcome->value,
            'tenant_id' => $tenantId,
            'processed_at' => now(),
        ]);
    }
}
