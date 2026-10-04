<?php

declare(strict_types=1);

namespace App\Exceptions\Plan;

use App\Exceptions\DomainException;

final class PlanInUseException extends DomainException
{
    public static function byTenants(string $name): self
    {
        return new self("O plano \"{$name}\" está em uso por tenants e não pode ser excluído. Inative-o.");
    }
}
