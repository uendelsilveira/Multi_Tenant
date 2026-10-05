<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * O que um evento de gateway significa para a plataforma, seja qual for o gateway (ADR-0006).
 */
enum BillingEventType: string
{
    case PaymentConfirmed = 'payment_confirmed';
    case PaymentOverdue = 'payment_overdue';
    case SubscriptionCanceled = 'subscription_canceled';
}
