<?php

/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

use App\Livewire\CreateTenant;
use App\Livewire\EditTenant;
use App\Livewire\TenantIndex;
use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains', []) as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            return view('welcome');
        });
    });
}

Route::middleware([
    'web',
    'universal',
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/tenants', TenantIndex::class)->name('tenants.index');
    Route::get('/tenants/create', CreateTenant::class)->name('tenants.create');
    Route::get('/tenants/{tenant}/edit', EditTenant::class)->name('tenants.edit');
});
