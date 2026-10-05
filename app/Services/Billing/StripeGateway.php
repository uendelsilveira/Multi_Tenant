<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\Billing\BillingEventDTO;
use App\DTOs\Billing\GatewaySubscriptionDTO;
use App\Enums\BillingCycle;
use App\Enums\BillingEventType;
use App\Enums\PaymentGateway;
use App\Exceptions\Billing\BillingException;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;

final class StripeGateway implements PaymentGatewayInterface
{
    /** Diferença máxima, em segundos, entre a assinatura do webhook e agora. */
    private const SIGNATURE_TOLERANCE = 300;

    /** @param array<string, mixed> $config config('billing.stripe') */
    public function __construct(
        private readonly HttpClient $http,
        private readonly array $config,
        private readonly int $firstDueInDays,
    ) {}

    public function gateway(): PaymentGateway
    {
        return PaymentGateway::Stripe;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['secret']);
    }

    /**
     * A fatura é enviada por e-mail ao pagador (send_invoice), sem cartão guardado.
     * O Stripe cobra por produto: o plano vira um produto lá no primeiro uso.
     */
    public function createSubscription(Tenant $tenant, Plan $plan, PlanPrice $price): GatewaySubscriptionDTO
    {
        $productId = $plan->stripe_product_id ?? $this->id($this->post('/products', ['name' => "Plano {$plan->name}"]), 'produto');

        $customerId = $this->id($this->post('/customers', array_filter([
            'name' => $tenant->legal_name,
            'email' => $tenant->contact_email,
            'phone' => $tenant->contact_phone,
            'metadata' => ['tenant_id' => $tenant->id],
        ], fn (mixed $value): bool => $value !== null && $value !== '')), 'cliente');

        $subscriptionId = $this->id($this->post('/subscriptions', [
            'customer' => $customerId,
            'collection_method' => 'send_invoice',
            'days_until_due' => $this->firstDueInDays,
            'items' => [['price_data' => $this->priceData($productId, $price)]],
            'metadata' => ['tenant_id' => $tenant->id],
        ]), 'assinatura');

        return new GatewaySubscriptionDTO($customerId, $subscriptionId, $productId);
    }

    public function updateSubscription(Subscription $subscription, Plan $plan, PlanPrice $price): void
    {
        $productId = $plan->stripe_product_id ?? $this->id($this->post('/products', ['name' => "Plano {$plan->name}"]), 'produto');

        $current = $this->get("/subscriptions/{$subscription->gateway_subscription_id}");
        $itemId = (string) ($current['items']['data'][0]['id'] ?? '');

        if ($itemId === '') {
            throw BillingException::unexpectedResponse($this->gateway(), 'item da assinatura');
        }

        $this->post("/subscriptions/{$subscription->gateway_subscription_id}", [
            'items' => [['id' => $itemId, 'price_data' => $this->priceData($productId, $price)]],
            // Sem cobrança proporcional: o novo valor vale a partir da próxima fatura.
            'proration_behavior' => 'none',
        ]);
    }

    /** Cabeçalho Stripe-Signature: t=<timestamp>,v1=<hmac sha256 de "timestamp.corpo">. */
    public function verifyWebhook(string $payload, array $headers): bool
    {
        $secret = (string) ($this->config['webhook_secret'] ?? '');
        $header = $headers['stripe-signature'] ?? '';

        if ($secret === '' || $header === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

            if ($key === 't') {
                $timestamp = $value;
            }

            if ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || ! ctype_digit($timestamp) || $signatures === []) {
            return false;
        }

        if (abs(Carbon::now()->getTimestamp() - (int) $timestamp) > self::SIGNATURE_TOLERANCE) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    public function parseWebhook(array $payload): ?BillingEventDTO
    {
        $eventId = (string) ($payload['id'] ?? '');
        $rawType = (string) ($payload['type'] ?? '');

        if ($eventId === '' || $rawType === '') {
            return null;
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $object = is_array($data['object'] ?? null) ? $data['object'] : [];

        $type = match ($rawType) {
            'invoice.paid' => BillingEventType::PaymentConfirmed,
            'invoice.overdue', 'invoice.payment_failed' => BillingEventType::PaymentOverdue,
            'customer.subscription.deleted' => BillingEventType::SubscriptionCanceled,
            default => null,
        };

        // Em eventos de assinatura o objeto é a própria assinatura; em faturas, o
        // campo da assinatura muda entre versões da API, então o pagador é a referência estável.
        $subscriptionId = str_starts_with($rawType, 'customer.subscription.')
            ? ($object['id'] ?? null)
            : ($object['subscription'] ?? null);

        return new BillingEventDTO(
            eventId: $eventId,
            rawType: $rawType,
            type: $type,
            customerId: self::nullable($object['customer'] ?? null),
            subscriptionId: self::nullable($subscriptionId),
        );
    }

    /** @return array<string, mixed> */
    private function priceData(string $productId, PlanPrice $price): array
    {
        return [
            'currency' => (string) ($this->config['currency'] ?? 'brl'),
            'product' => $productId,
            'unit_amount' => (int) round(((float) $price->price) * 100),
            'recurring' => match ($price->billing_cycle) {
                BillingCycle::Monthly => ['interval' => 'month', 'interval_count' => 1],
                BillingCycle::Semiannual => ['interval' => 'month', 'interval_count' => 6],
                BillingCycle::Annual => ['interval' => 'year', 'interval_count' => 1],
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function post(string $path, array $data): array
    {
        $response = $this->request()->asForm()->post($path, $data);

        if ($response->failed()) {
            throw BillingException::gatewayRejected($this->gateway(), $response->status(), $this->errorMessage($response));
        }

        /** @var array<string, mixed> $json */
        $json = (array) $response->json();

        return $json;
    }

    /** @return array<string, mixed> */
    private function get(string $path): array
    {
        $response = $this->request()->get($path);

        if ($response->failed()) {
            throw BillingException::gatewayRejected($this->gateway(), $response->status(), $this->errorMessage($response));
        }

        /** @var array<string, mixed> $json */
        $json = (array) $response->json();

        return $json;
    }

    /** O Stripe devolve o motivo em error.message. */
    private function errorMessage(Response $response): string
    {
        $message = $response->json('error.message');

        return is_string($message) && $message !== '' ? $message : $response->body();
    }

    private function request(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw BillingException::gatewayNotConfigured($this->gateway());
        }

        return $this->http
            ->baseUrl(rtrim((string) ($this->config['base_url'] ?? ''), '/'))
            ->withToken((string) $this->config['secret'])
            ->acceptJson()
            ->timeout(15);
    }

    /** @param array<string, mixed> $response */
    private function id(array $response, string $what): string
    {
        $id = (string) ($response['id'] ?? '');

        if ($id === '') {
            throw BillingException::unexpectedResponse($this->gateway(), $what);
        }

        return $id;
    }

    private static function nullable(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
