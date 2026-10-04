<?php

declare(strict_types=1);

namespace App\Exceptions\Plan;

use App\Enums\BillingCycle;
use App\Exceptions\DomainException;

final class PlanNotAvailableException extends DomainException
{
    public static function inactive(string $name): self
    {
        return new self("O plano \"{$name}\" está inativo e não pode ser contratado.");
    }

    public static function cycleNotOffered(string $name, BillingCycle $cycle): self
    {
        return new self("O plano \"{$name}\" não oferece o ciclo {$cycle->label()}.");
    }
}
