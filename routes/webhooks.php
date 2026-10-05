<?php

declare(strict_types=1);

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhooks de cobrança
|--------------------------------------------------------------------------
|
| Sem sessão, cookies nem CSRF: quem chama é o gateway, e a autenticidade é
| conferida pelo token (Asaas) ou pela assinatura (Stripe) de cada requisição.
|
*/

Route::post('/webhooks/{gateway}', WebhookController::class)
    ->whereIn('gateway', ['asaas', 'stripe'])
    ->middleware('throttle:300,1')
    ->name('webhooks.receive');
