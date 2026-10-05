<?php

declare(strict_types=1);
/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\TenantAdminPanelProvider;
use App\Providers\Filament\TenantCustomerPanelProvider;
use App\Providers\Filament\TenantUserPanelProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    TenantAdminPanelProvider::class,
    TenantUserPanelProvider::class,
    TenantCustomerPanelProvider::class,
    TenancyServiceProvider::class,
];
