<?php

declare(strict_types=1);

namespace App\Exceptions\Role;

use App\Exceptions\DomainException;

final class RoleNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Perfil [{$id}] não encontrado.");
    }
}
