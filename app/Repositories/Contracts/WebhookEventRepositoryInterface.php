<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\PaymentGateway;
use App\Enums\WebhookOutcome;
use App\Models\WebhookEvent;

interface WebhookEventRepositoryInterface
{
    /**
     * Grava o evento. Devolve null se ele já tinha sido recebido antes (RN18).
     *
     * @param  array<string, mixed>  $payload
     */
    public function storeOnce(PaymentGateway $gateway, string $eventId, string $type, array $payload): ?WebhookEvent;

    public function find(int $id): ?WebhookEvent;

    public function markProcessed(WebhookEvent $event, WebhookOutcome $outcome, ?string $tenantId): void;
}
