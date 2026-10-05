<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Billing\GatewaySubscriptionDTO;
use App\Enums\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Carbon\CarbonInterface;

final class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function findByTenant(string $tenantId): ?Subscription
    {
        return Subscription::query()->where('tenant_id', $tenantId)->first();
    }

    public function findByGatewayReference(PaymentGateway $gateway, ?string $subscriptionId, ?string $customerId): ?Subscription
    {
        if ($subscriptionId !== null) {
            $found = Subscription::query()
                ->where('gateway', $gateway->value)
                ->where('gateway_subscription_id', $subscriptionId)
                ->first();

            if ($found !== null) {
                return $found;
            }
        }

        if ($customerId === null) {
            return null;
        }

        return Subscription::query()
            ->where('gateway', $gateway->value)
            ->where('gateway_customer_id', $customerId)
            ->first();
    }

    public function markCreated(Subscription $subscription, GatewaySubscriptionDTO $created): void
    {
        $subscription->update([
            'gateway_customer_id' => $created->customerId,
            'gateway_subscription_id' => $created->subscriptionId,
            'status' => SubscriptionStatus::Active->value,
            'last_error' => null,
        ]);
    }

    public function markFailed(Subscription $subscription, string $error): void
    {
        $subscription->update([
            'status' => SubscriptionStatus::Failed->value,
            'last_error' => mb_substr($error, 0, 1000),
        ]);
    }

    public function markPaid(Subscription $subscription): void
    {
        $subscription->update([
            'status' => SubscriptionStatus::Active->value,
            'overdue_since' => null,
            'last_paid_at' => now(),
        ]);
    }

    public function markOverdue(Subscription $subscription): void
    {
        $subscription->update([
            'status' => SubscriptionStatus::Overdue->value,
            'overdue_since' => $subscription->overdue_since ?? now(),
        ]);
    }

    public function markCanceled(Subscription $subscription): void
    {
        $subscription->update(['status' => SubscriptionStatus::Canceled->value]);
    }

    public function overdueSince(CarbonInterface $cutoff): array
    {
        return array_values(Subscription::query()
            ->where('status', SubscriptionStatus::Overdue->value)
            ->where('overdue_since', '<=', $cutoff)
            ->orderBy('id')
            ->get()
            ->all());
    }
}
