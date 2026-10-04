<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use Filament\Resources\Pages\CreateRecord;

final class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /**
     * Desvio conhecido do padrão técnico: a criação do domínio é regra de
     * provisionamento e deve sair daqui para CreateTenantAction (RF01, RF02).
     * Comportamento preservado da versão v3 até a fatia 1 ser construída.
     * Ver docs/04-arquitetura/estado-atual.md.
     */
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
