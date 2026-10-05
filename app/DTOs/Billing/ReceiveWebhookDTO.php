<?php

declare(strict_types=1);

namespace App\DTOs\Billing;

use App\Enums\PaymentGateway;

final class ReceiveWebhookDTO
{
    /** @param array<string, string> $headers nomes em minúsculas */
    public function __construct(
        public readonly PaymentGateway $gateway,
        public readonly string $payload,
        public readonly array $headers,
    ) {}
}
