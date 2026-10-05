<?php

declare(strict_types=1);
/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Stancl\Tenancy\Exceptions\TenantDatabaseDoesNotExistException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::group([], __DIR__.'/../routes/webhooks.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(fn (TenantDatabaseDoesNotExistException $e) => response()
            ->view('tenant.provisioning', status: 503)
            ->header('Retry-After', '30'));

        $exceptions->render(fn (TenantCouldNotBeIdentifiedOnDomainException $e) => response('Página não encontrada.', 404));

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
