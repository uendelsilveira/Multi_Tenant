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

final class AsaasGateway implements PaymentGatewayInterface
{
    /** @param array<string, mixed> $config config('billing.asaas') */
    public function __construct(
        private readonly HttpClient $http,
        private readonly array $config,
        private readonly int $firstDueInDays,
    ) {}

    public function gateway(): PaymentGateway
    {
        return PaymentGateway::Asaas;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config['api_key']);
    }

    public function createSubscription(Tenant $tenant, Plan $plan, PlanPrice $price): GatewaySubscriptionDTO
    {
        $customer = $this->post('/customers', array_filter([
            'name' => $tenant->legal_name,
            'cpfCnpj' => $tenant->document,
            'email' => $tenant->contact_email,
            'mobilePhone' => $tenant->contact_phone,
            'postalCode' => $tenant->zip_code,
            'address' => $tenant->street,
            'addressNumber' => $tenant->number,
            'complement' => $tenant->complement,
            'province' => $tenant->district,
            'externalReference' => $tenant->id,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));

        $customerId = (string) ($customer['id'] ?? '');

        if ($customerId === '') {
            throw BillingException::unexpectedResponse($this->gateway(), 'cliente');
        }

        $subscription = $this->post('/subscriptions', [
            'customer' => $customerId,
            'billingType' => (string) ($this->config['billing_type'] ?? 'UNDEFINED'),
            'value' => (float) $price->price,
            'nextDueDate' => Carbon::now()->addDays($this->firstDueInDays)->toDateString(),
            'cycle' => $this->cycle($price->billing_cycle),
            'description' => "Plano {$plan->name}",
            'externalReference' => $tenant->id,
        ]);

        $subscriptionId = (string) ($subscription['id'] ?? '');

        if ($subscriptionId === '') {
            throw BillingException::unexpectedResponse($this->gateway(), 'assinatura');
        }

        return new GatewaySubscriptionDTO($customerId, $subscriptionId);
    }

    public function updateSubscription(Subscription $subscription, Plan $plan, PlanPrice $price): void
    {
        // No Asaas a alteração só vale para as cobranças futuras.
        $this->post("/subscriptions/{$subscription->gateway_subscription_id}", [
            'value' => (float) $price->price,
            'cycle' => $this->cycle($price->billing_cycle),
            'description' => "Plano {$plan->name}",
        ], 'PUT');
    }

    public function verifyWebhook(string $payload, array $headers): bool
    {
        $expected = (string) ($this->config['webhook_token'] ?? '');
        $received = $headers['asaas-access-token'] ?? '';

        return $expected !== '' && hash_equals($expected, $received);
    }

    public function parseWebhook(array $payload): ?BillingEventDTO
    {
        $eventId = (string) ($payload['id'] ?? '');
        $rawType = (string) ($payload['event'] ?? '');

        if ($eventId === '' || $rawType === '') {
            return null;
        }

        $payment = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
        $subscription = is_array($payload['subscription'] ?? null) ? $payload['subscription'] : [];

        $type = match ($rawType) {
            'PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED' => BillingEventType::PaymentConfirmed,
            'PAYMENT_OVERDUE' => BillingEventType::PaymentOverdue,
            'SUBSCRIPTION_DELETED', 'SUBSCRIPTION_INACTIVATED' => BillingEventType::SubscriptionCanceled,
            default => null,
        };

        return new BillingEventDTO(
            eventId: $eventId,
            rawType: $rawType,
            type: $type,
            customerId: self::nullable($payment['customer'] ?? $subscription['customer'] ?? null),
            subscriptionId: self::nullable($payment['subscription'] ?? $subscription['id'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function post(string $path, array $data, string $method = 'POST'): array
    {
        $response = $this->request()->send($method, $path, ['json' => $data]);

        if ($response->failed()) {
            throw BillingException::gatewayRejected($this->gateway(), $response->status(), $this->errorMessage($response));
        }

        /** @var array<string, mixed> $json */
        $json = (array) $response->json();

        return $json;
    }

    /** O Asaas devolve os motivos em errors[].description. */
    private function errorMessage(Response $response): string
    {
        $descriptions = [];

        foreach ((array) $response->json('errors', []) as $error) {
            if (is_array($error) && isset($error['description'])) {
                $descriptions[] = (string) $error['description'];
            }
        }

        return $descriptions === [] ? $response->body() : implode(' ', $descriptions);
    }

    private function request(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw BillingException::gatewayNotConfigured($this->gateway());
        }

        return $this->http
            ->baseUrl(rtrim((string) ($this->config['base_url'] ?? ''), '/'))
            ->withHeaders(['access_token' => (string) $this->config['api_key']])
            ->withUserAgent((string) config('app.name', 'Laravel'))
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }

    private function cycle(BillingCycle $cycle): string
    {
        return match ($cycle) {
            BillingCycle::Monthly => 'MONTHLY',
            BillingCycle::Semiannual => 'SEMIANNUALLY',
            BillingCycle::Annual => 'YEARLY',
        };
    }

    private static function nullable(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
