<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cobrança
    |--------------------------------------------------------------------------
    |
    | grace_days: dias entre o vencimento e a suspensão do tenant (RN47).
    | first_due_in_days: prazo, a partir do cadastro, para o primeiro vencimento.
    |
    */

    'grace_days' => (int) env('BILLING_GRACE_DAYS', 10),

    'first_due_in_days' => (int) env('BILLING_FIRST_DUE_IN_DAYS', 7),

    'asaas' => [
        // Produção: https://api.asaas.com/v3
        'base_url' => env('ASAAS_BASE_URL', 'https://api-sandbox.asaas.com/v3'),
        'api_key' => env('ASAAS_API_KEY'),
        // Token definido no cadastro do webhook no Asaas; chega no header asaas-access-token.
        'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),
        // UNDEFINED deixa o pagador escolher entre boleto, Pix e cartão.
        'billing_type' => env('ASAAS_BILLING_TYPE', 'UNDEFINED'),
    ],

    'stripe' => [
        'base_url' => env('STRIPE_BASE_URL', 'https://api.stripe.com/v1'),
        'secret' => env('STRIPE_SECRET'),
        // Segredo de assinatura do endpoint de webhook (whsec_...).
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'brl'),
    ],

];
