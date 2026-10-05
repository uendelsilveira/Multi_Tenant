<?php

declare(strict_types=1);

namespace App\Exceptions\Customer;

use App\Exceptions\DomainException;

final class CustomerNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Cliente [{$id}] não encontrado.");
    }
}
