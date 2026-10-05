<?php

declare(strict_types=1);

namespace App\Exceptions\TenantDomain;

use App\Exceptions\DomainException;

final class TenantDomainNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Domínio [{$id}] não encontrado.");
    }
}
