<?php

declare(strict_types=1);

namespace App\DTOs\Billing;

use App\Enums\BillingEventType;

/**
 * Evento de gateway já traduzido para o vocabulário da plataforma.
 * `type` nulo significa um evento que não tem efeito aqui.
 */
final class BillingEventDTO
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $rawType,
        public readonly ?BillingEventType $type,
        public readonly ?string $customerId,
        public readonly ?string $subscriptionId,
    ) {}
}
