<?php

declare(strict_types=1);

namespace App\Exceptions\Tenant;

use App\Exceptions\DomainException;

final class TenantProvisioningException extends DomainException
{
    public static function alreadyProvisioned(string $tenantId): self
    {
        return new self("O tenant [{$tenantId}] já está provisionado.");
    }

    public static function missingAdminContact(string $tenantId): self
    {
        return new self("O tenant [{$tenantId}] não tem responsável e e-mail de contato para criar o admin inicial.");
    }

    public static function databaseNameUnavailable(string $tenantId): self
    {
        return new self("Não foi possível determinar o nome do banco do tenant [{$tenantId}].");
    }
}
