<?php

declare(strict_types=1);

namespace App\Exceptions\Tenant;

use App\Exceptions\DomainException;

final class TenantNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self("Tenant [{$id}] não encontrado.");
    }
}
