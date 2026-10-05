<?php

declare(strict_types=1);

namespace App\Listeners\Billing;

use App\Events\Billing\WebhookEventReceived;
use App\Jobs\ProcessWebhookEventJob;

final class DispatchWebhookProcessing
{
    public function handle(WebhookEventReceived $event): void
    {
        ProcessWebhookEventJob::dispatch($event->webhookEventId);
    }
}
