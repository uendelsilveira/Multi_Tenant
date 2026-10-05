<?php

declare(strict_types=1);

/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

namespace Tests;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /**
     * Um teste pode decidir a resposta do gateway para uma requisição;
     * devolvendo null, vale a resposta padrão abaixo.
     *
     * @var (Closure(Request): (PromiseInterface|null))|null
     */
    protected ?Closure $gatewayResponse = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Credenciais de teste e gateways falsos: cadastrar um tenant cria a
        // assinatura "no gateway", e nenhum teste pode chamar a API real.
        config([
            'billing.asaas.api_key' => 'asaas-test-key',
            'billing.asaas.webhook_token' => 'asaas-webhook-token',
            'billing.stripe.secret' => 'sk_test_fake',
            'billing.stripe.webhook_secret' => 'whsec_test_fake',
        ]);

        Http::preventStrayRequests();

        Http::fake(function (Request $request) {
            $custom = $this->gatewayResponse !== null ? ($this->gatewayResponse)($request) : null;

            if ($custom !== null) {
                return $custom;
            }

            $path = (string) parse_url($request->url(), PHP_URL_PATH);

            return match (true) {
                str_ends_with($path, '/customers') => Http::response(['id' => 'cus_test_'.substr(md5($request->body()), 0, 10)]),
                str_ends_with($path, '/products') => Http::response(['id' => 'prod_test_1']),
                str_ends_with($path, '/subscriptions') => Http::response(['id' => 'sub_test_'.substr(md5($request->body()), 0, 10)]),
                str_contains($path, '/subscriptions/') && $request->method() === 'GET' => Http::response(['id' => 'sub_test', 'items' => ['data' => [['id' => 'si_test_1']]]]),
                str_contains($path, '/subscriptions/') => Http::response(['id' => 'sub_test']),
                default => Http::response(['error' => 'rota não prevista nos testes'], 404),
            };
        });
    }
}
