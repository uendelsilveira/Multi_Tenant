<?php

declare(strict_types=1);

namespace App\Exceptions\Billing;

use App\Enums\PaymentGateway;
use App\Exceptions\DomainException;

final class BillingException extends DomainException
{
    public static function gatewayNotConfigured(PaymentGateway $gateway): self
    {
        return new self("O gateway {$gateway->label()} não está configurado: faltam as credenciais no ambiente.");
    }

    public static function gatewayRejected(PaymentGateway $gateway, int $status, string $body): self
    {
        return new self("O {$gateway->label()} recusou a requisição (HTTP {$status}): ".mb_substr($body, 0, 500));
    }

    public static function unexpectedResponse(PaymentGateway $gateway, string $what): self
    {
        return new self("O {$gateway->label()} respondeu sem o identificador de {$what}.");
    }

    public static function subscriptionNotFound(string $tenantId): self
    {
        return new self("O tenant [{$tenantId}] não tem assinatura cadastrada.");
    }

    public static function alreadyCreated(string $tenantId): self
    {
        return new self("A assinatura do tenant [{$tenantId}] já foi criada no gateway.");
    }

    public static function missingPlanPrice(string $tenantId): self
    {
        return new self("O tenant [{$tenantId}] não tem plano e ciclo com preço para cobrar.");
    }
}
