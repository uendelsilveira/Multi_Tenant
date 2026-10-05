<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Billing\ReceiveWebhookDTO;
use App\Models\WebhookEvent;
use App\Repositories\Contracts\WebhookEventRepositoryInterface;
use App\Services\Billing\PaymentGatewayRegistry;

/**
 * Entrada dos webhooks: confere a origem e grava o evento, sem interpretar.
 */
final class WebhookService
{
    public function __construct(
        private readonly WebhookEventRepositoryInterface $events,
        private readonly PaymentGatewayRegistry $gateways,
    ) {}

    public function isAuthentic(ReceiveWebhookDTO $dto): bool
    {
        return $this->gateways->for($dto->gateway)->verifyWebhook($dto->payload, $dto->headers);
    }

    /** Devolve o evento gravado, ou null se o corpo não é um evento ou já tinha chegado antes. */
    public function store(ReceiveWebhookDTO $dto): ?WebhookEvent
    {
        $payload = json_decode($dto->payload, true);

        if (! is_array($payload)) {
            return null;
        }

        /** @var array<string, mixed> $payload */
        $parsed = $this->gateways->for($dto->gateway)->parseWebhook($payload);

        if ($parsed === null) {
            return null;
        }

        return $this->events->storeOnce($dto->gateway, $parsed->eventId, $parsed->rawType, $payload);
    }
}
