<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BillingEventType;
use App\Enums\TenantStatus;
use App\Enums\WebhookOutcome;
use App\Exceptions\Billing\BillingException;
use App\Exceptions\Tenant\TenantNotFoundException;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\WebhookEventRepositoryInterface;
use App\Services\Billing\PaymentGatewayRegistry;
use Illuminate\Support\Carbon;

/**
 * Cobrança dos tenants: cria a assinatura no gateway, interpreta os eventos
 * que ele envia e aplica a carência. Nunca altera a situação do tenant por
 * conta própria: pede ao TenantStatusService, que respeita a trava manual.
 */
final class BillingService
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly WebhookEventRepositoryInterface $events,
        private readonly TenantRepositoryInterface $tenants,
        private readonly PlanRepositoryInterface $plans,
        private readonly PaymentGatewayRegistry $gateways,
        private readonly TenantStatusService $status,
        private readonly int $graceDays,
    ) {}

    /**
     * Cria o pagador e a assinatura no gateway. Pode ser repetido: se a
     * assinatura já existe lá, não cria outra. Devolve se criou agora.
     */
    public function startSubscription(string $tenantId): bool
    {
        $subscription = $this->subscriptions->findByTenant($tenantId) ?? throw BillingException::subscriptionNotFound($tenantId);

        if ($subscription->gateway_subscription_id !== null) {
            return false;
        }

        $gateway = $this->gateways->for($subscription->gateway);

        // Falta de credencial ou de preço não se resolve tentando de novo: fica
        // registrado como falha, à espera da correção e de uma nova tentativa manual.
        try {
            if (! $gateway->isConfigured()) {
                throw BillingException::gatewayNotConfigured($subscription->gateway);
            }

            [$tenant, $plan, $price] = $this->billable($tenantId);
        } catch (BillingException $e) {
            $this->subscriptions->markFailed($subscription, $e->getMessage());

            return false;
        }

        // Recusa ou indisponibilidade do gateway pode ser passageira: registra e
        // deixa a exceção subir, para a fila tentar de novo.
        try {
            $created = $gateway->createSubscription($tenant, $plan, $price);
        } catch (BillingException $e) {
            $this->subscriptions->markFailed($subscription, $e->getMessage());

            throw $e;
        }

        if ($created->productId !== null && $created->productId !== $plan->stripe_product_id) {
            $this->plans->setStripeProductId($plan, $created->productId);
        }

        $this->subscriptions->markCreated($subscription, $created);

        return true;
    }

    /** Só faz sentido tentar de novo o que ainda não existe no gateway. */
    public function assertCanRetry(string $tenantId): void
    {
        $subscription = $this->subscriptions->findByTenant($tenantId) ?? throw BillingException::subscriptionNotFound($tenantId);

        if ($subscription->gateway_subscription_id !== null) {
            throw BillingException::alreadyCreated($tenantId);
        }
    }

    /** Leva o novo plano ou ciclo às próximas cobranças, sem valor proporcional. */
    public function syncSubscriptionPrice(string $tenantId): bool
    {
        $subscription = $this->subscriptions->findByTenant($tenantId);

        if ($subscription === null || $subscription->gateway_subscription_id === null) {
            return false;
        }

        [, $plan, $price] = $this->billable($tenantId);

        $this->gateways->for($subscription->gateway)->updateSubscription($subscription, $plan, $price);

        return true;
    }

    /**
     * Aplica um evento recebido. Devolve o id do tenant quando a situação dele
     * mudou por causa do evento, ou null.
     */
    public function handleEvent(int $webhookEventId): ?string
    {
        $event = $this->events->find($webhookEventId);

        // Reprocessar um evento já aplicado não pode ter efeito (RN18).
        if ($event === null || $event->processed_at !== null) {
            return null;
        }

        $parsed = $this->gateways->for($event->gateway)->parseWebhook($event->payload);

        if ($parsed === null || $parsed->type === null) {
            $this->events->markProcessed($event, WebhookOutcome::Ignored, null);

            return null;
        }

        $subscription = $this->subscriptions->findByGatewayReference($event->gateway, $parsed->subscriptionId, $parsed->customerId);

        if ($subscription === null) {
            $this->events->markProcessed($event, WebhookOutcome::Unmatched, null);

            return null;
        }

        $changed = match ($parsed->type) {
            BillingEventType::PaymentConfirmed => $this->paymentConfirmed($subscription),
            BillingEventType::PaymentOverdue => $this->paymentOverdue($subscription),
            BillingEventType::SubscriptionCanceled => $this->subscriptionCanceled($subscription),
        };

        $this->events->markProcessed($event, WebhookOutcome::Applied, $subscription->tenant_id);

        return $changed ? $subscription->tenant_id : null;
    }

    /**
     * Suspende quem está vencido há mais tempo que a carência (RN47).
     *
     * @return list<string> ids dos tenants suspensos agora
     */
    public function enforceGracePeriod(): array
    {
        $cutoff = Carbon::now()->subDays($this->graceDays);
        $suspended = [];

        foreach ($this->subscriptions->overdueSince($cutoff) as $subscription) {
            $changed = $this->changeStatus($subscription, TenantStatus::Suspended, "Pagamento vencido há mais de {$this->graceDays} dias.");

            if ($changed) {
                $suspended[] = $subscription->tenant_id;
            }
        }

        return $suspended;
    }

    private function paymentConfirmed(Subscription $subscription): bool
    {
        $this->subscriptions->markPaid($subscription);

        return $this->changeStatus($subscription, TenantStatus::Active, 'Pagamento confirmado.');
    }

    /** O vencimento só inicia a carência; quem suspende é enforceGracePeriod. */
    private function paymentOverdue(Subscription $subscription): bool
    {
        $this->subscriptions->markOverdue($subscription);

        return false;
    }

    /** Cancelamento vale como suspensão, com os dados preservados (RN48). */
    private function subscriptionCanceled(Subscription $subscription): bool
    {
        $this->subscriptions->markCanceled($subscription);

        return $this->changeStatus($subscription, TenantStatus::Suspended, 'Assinatura cancelada no gateway.');
    }

    /** Tenant excluído não tem situação a alterar: a cobrança fica registrada e nada mais acontece. */
    private function changeStatus(Subscription $subscription, TenantStatus $status, string $reason): bool
    {
        try {
            return $this->status->changeFromGateway($subscription->tenant_id, $status, $reason);
        } catch (TenantNotFoundException) {
            return false;
        }
    }

    /** @return array{Tenant, Plan, PlanPrice} */
    private function billable(string $tenantId): array
    {
        $tenant = $this->tenants->find($tenantId, withTrashed: true) ?? throw TenantNotFoundException::withId($tenantId);
        $plan = $tenant->plan_id === null ? null : $this->plans->find($tenant->plan_id);

        /** @var PlanPrice|null $price */
        $price = $plan?->prices->first(fn (PlanPrice $price): bool => $price->billing_cycle === $tenant->billing_cycle);

        if ($plan === null || $price === null) {
            throw BillingException::missingPlanPrice($tenantId);
        }

        return [$tenant, $plan, $price];
    }
}
