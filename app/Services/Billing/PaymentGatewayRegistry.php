<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentGateway;
use InvalidArgumentException;

/**
 * Entrega a implementação de cada gateway.
 */
final class PaymentGatewayRegistry
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    public function __construct(PaymentGatewayInterface ...$gateways)
    {
        foreach ($gateways as $gateway) {
            $this->gateways[$gateway->gateway()->value] = $gateway;
        }
    }

    public function for(PaymentGateway $gateway): PaymentGatewayInterface
    {
        return $this->gateways[$gateway->value]
            ?? throw new InvalidArgumentException("Gateway [{$gateway->value}] não registrado.");
    }
}
