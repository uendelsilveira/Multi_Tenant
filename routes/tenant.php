<?php

/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

declare(strict_types=1);

use App\Enums\DomainPanel;
use App\Http\Middleware\EnsureTenantIsNotSuspended;
use App\Http\Middleware\EnsureTenantIsProvisioned;
use App\Http\Middleware\InitializeTenancyForTenantDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Rotas de tenant
|--------------------------------------------------------------------------
|
| As telas ficam nos painéis do Filament. Aqui só a raiz do domínio, que
| leva ao painel para o qual aquele domínio aponta.
|
*/

Route::middleware([
    'web',
    InitializeTenancyForTenantDomain::class,
    PreventAccessFromCentralDomains::class,
    EnsureTenantIsProvisioned::class,
    EnsureTenantIsNotSuspended::class,
])->group(function () {
    Route::get('/', function (Request $request) {
        $panel = $request->attributes->get(InitializeTenancyForTenantDomain::PANEL_ATTRIBUTE);

        return redirect('/'.($panel instanceof DomainPanel ? $panel->path() : DomainPanel::Admin->path()));
    });
});
