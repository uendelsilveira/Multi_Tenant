<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Actions\BaseAction;
use App\DTOs\Billing\ReceiveWebhookDTO;
use App\Events\Billing\WebhookEventReceived;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Log;

final class ReceiveWebhookAction extends BaseAction
{
    public function __construct(
        private readonly WebhookService $service,
    ) {}

    /** Devolve false quando a requisição não veio do gateway. */
    public function execute(ReceiveWebhookDTO $dto): bool
    {
        if (! $this->service->isAuthentic($dto)) {
            Log::warning('webhook.rejected', ['gateway' => $dto->gateway->value]);

            return false;
        }

        $event = $this->service->store($dto);

        // Evento repetido ou sem identificador: aceito, sem novo processamento.
        if ($event !== null) {
            event(new WebhookEventReceived($event->id));

            Log::info('webhook.received', ['gateway' => $dto->gateway->value, 'webhook_event_id' => $event->id, 'type' => $event->type]);
        }

        return true;
    }
}
