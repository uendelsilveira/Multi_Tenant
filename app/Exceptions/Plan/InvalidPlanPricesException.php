<?php

declare(strict_types=1);

namespace App\Exceptions\Plan;

use App\Enums\BillingCycle;
use App\Exceptions\DomainException;

final class InvalidPlanPricesException extends DomainException
{
    public static function none(): self
    {
        return new self('O plano precisa ter preço em pelo menos um ciclo.');
    }

    public static function duplicatedCycle(BillingCycle $cycle): self
    {
        return new self("O ciclo {$cycle->label()} foi informado mais de uma vez.");
    }

    public static function invalidAmount(BillingCycle $cycle): self
    {
        return new self("O preço do ciclo {$cycle->label()} precisa ser um valor igual ou maior que zero.");
    }

    public static function cycleInUse(BillingCycle $cycle): self
    {
        return new self("O ciclo {$cycle->label()} não pode ser retirado: há tenants contratados nele.");
    }
}
