<?php

declare(strict_types=1);

namespace App\Exceptions\Plan;

use App\Exceptions\DomainException;

final class PlanNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Plano [{$id}] não encontrado.");
    }
}
