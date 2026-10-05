<?php

declare(strict_types=1);

namespace App\Exceptions\TenantUser;

use App\Exceptions\DomainException;

final class TenantUserNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Pessoa [{$id}] não encontrada.");
    }
}
