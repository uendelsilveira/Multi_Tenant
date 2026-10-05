<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\Billing\BillingEventDTO;
use App\DTOs\Billing\GatewaySubscriptionDTO;
use App\Enums\PaymentGateway;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;

/**
 * Um gateway de cobrança. A plataforma só fala com gateways por aqui (ADR-0006).
 */
interface PaymentGatewayInterface
{
    public function gateway(): PaymentGateway;

    /** Sem credenciais não há o que chamar. */
    public function isConfigured(): bool;

    /** Cria o pagador e a cobrança recorrente do plano, no ciclo e valor indicados. */
    public function createSubscription(Tenant $tenant, Plan $plan, PlanPrice $price): GatewaySubscriptionDTO;

    /** Ajusta valor e ciclo das próximas cobranças, sem cobrança proporcional. */
    public function updateSubscription(Subscription $subscription, Plan $plan, PlanPrice $price): void;

    /**
     * Confere se a requisição veio mesmo do gateway.
     *
     * @param  array<string, string>  $headers  nomes em minúsculas
     */
    public function verifyWebhook(string $payload, array $headers): bool;

    /**
     * Traduz o corpo do webhook. Devolve null quando não há identificador de evento.
     *
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhook(array $payload): ?BillingEventDTO;
}
