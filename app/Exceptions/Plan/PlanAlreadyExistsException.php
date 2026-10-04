<?php

declare(strict_types=1);

namespace App\Exceptions\Plan;

use App\Exceptions\DomainException;

final class PlanAlreadyExistsException extends DomainException
{
    public static function withName(string $name): self
    {
        return new self("Já existe um plano chamado \"{$name}\".");
    }
}
