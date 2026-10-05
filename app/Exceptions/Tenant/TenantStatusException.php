<?php

declare(strict_types=1);

namespace App\Exceptions\Tenant;

use App\Exceptions\DomainException;

final class TenantStatusException extends DomainException
{
    public static function reasonRequired(): self
    {
        return new self('Informe o motivo da alteração de situação.');
    }

    public static function lockMustBeInTheFuture(): self
    {
        return new self('A data da trava precisa estar no futuro.');
    }

    public static function nothingToChange(): self
    {
        return new self('O tenant já está nessa situação, com essa mesma trava.');
    }
}
