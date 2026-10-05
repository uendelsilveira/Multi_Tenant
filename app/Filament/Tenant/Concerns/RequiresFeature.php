<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Concerns;

use App\Services\TenantFeatureService;

/**
 * Para resources e páginas de um módulo: com a funcionalidade inativa, o item
 * some do menu e a tela não abre (RF15). Serve tanto em Resource quanto em Page.
 *
 * Uso: use RequiresFeature; e implementar requiredFeature() devolvendo a chave,
 * por exemplo 'helpdesk.tickets'.
 */
trait RequiresFeature
{
    abstract protected static function requiredFeature(): string;

    public static function canAccess(): bool
    {
        return app(TenantFeatureService::class)->isActiveForCurrentTenant(static::requiredFeature())
            && parent::canAccess();
    }
}
