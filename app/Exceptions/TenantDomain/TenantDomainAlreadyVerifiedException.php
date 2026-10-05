<?php

declare(strict_types=1);

namespace App\Exceptions\TenantDomain;

use App\Exceptions\DomainException;

final class TenantDomainAlreadyVerifiedException extends DomainException
{
    public static function forHost(string $host): self
    {
        return new self("O domínio {$host} já está verificado.");
    }
}
