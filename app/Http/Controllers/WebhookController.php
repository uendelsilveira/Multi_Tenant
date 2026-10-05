<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Billing\ReceiveWebhookAction;
use App\DTOs\Billing\ReceiveWebhookDTO;
use App\Enums\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Entrada dos webhooks de cobrança. Só confere, grava e responde; o
 * processamento acontece em fila (RNF04).
 */
final class WebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, ReceiveWebhookAction $action): Response
    {
        $headers = [];

        foreach ($request->headers->all() as $name => $values) {
            $headers[strtolower((string) $name)] = (string) ($values[0] ?? '');
        }

        $accepted = $action->execute(new ReceiveWebhookDTO(
            gateway: PaymentGateway::from($gateway),
            payload: $request->getContent(),
            headers: $headers,
        ));

        return response('', $accepted ? Response::HTTP_OK : Response::HTTP_UNAUTHORIZED);
    }
}
