<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Billing\GatewaySubscriptionDTO;
use App\Enums\PaymentGateway;
use App\Models\Subscription;
use Carbon\CarbonInterface;

interface SubscriptionRepositoryInterface
{
    public function findByTenant(string $tenantId): ?Subscription;

    /** Localiza pelo identificador da assinatura no gateway ou, na falta dele, pelo do pagador. */
    public function findByGatewayReference(PaymentGateway $gateway, ?string $subscriptionId, ?string $customerId): ?Subscription;

    public function markCreated(Subscription $subscription, GatewaySubscriptionDTO $created): void;

    public function markFailed(Subscription $subscription, string $error): void;

    public function markPaid(Subscription $subscription): void;

    /** Guarda o momento do primeiro vencimento; avisos repetidos não reiniciam a carência. */
    public function markOverdue(Subscription $subscription): void;

    public function markCanceled(Subscription $subscription): void;

    /**
     * Assinaturas vencidas desde antes do momento indicado.
     *
     * @return list<Subscription>
     */
    public function overdueSince(CarbonInterface $cutoff): array;
}
