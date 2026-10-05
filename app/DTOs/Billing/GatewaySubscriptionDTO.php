<?php

declare(strict_types=1);

namespace App\DTOs\Billing;

/**
 * O que o gateway devolve ao criar a cobrança recorrente de um tenant.
 */
final class GatewaySubscriptionDTO
{
    public function __construct(
        public readonly string $customerId,
        public readonly string $subscriptionId,
        // Identificador do plano no gateway, quando o gateway exige um (Stripe).
        public readonly ?string $productId = null,
    ) {}
}
