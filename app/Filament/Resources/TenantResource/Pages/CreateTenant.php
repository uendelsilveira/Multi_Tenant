<?php
/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\Tenant;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function afterCreate(): void
    {
        /** @var Tenant $tenant */
        $tenant = $this->record;
        /** @var string $subdomain */
        $subdomain = $tenant->tenant_name ?? $tenant->id;

        if (! $tenant->domains()->where('domain', $subdomain)->exists()) {
            $tenant->domains()->create([
                'domain' => $subdomain,
            ]);
        }
    }
}
