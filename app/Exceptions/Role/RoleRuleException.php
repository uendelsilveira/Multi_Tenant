<?php

declare(strict_types=1);

namespace App\Exceptions\Role;

use App\Exceptions\DomainException;

final class RoleRuleException extends DomainException
{
    public static function nameTaken(string $name): self
    {
        return new self("Já existe um perfil chamado \"{$name}\".");
    }

    public static function systemRole(string $name): self
    {
        return new self("\"{$name}\" é um perfil de sistema e não pode ser alterado nem excluído.");
    }

    public static function inUse(string $name): self
    {
        return new self("O perfil \"{$name}\" tem pessoas vinculadas e não pode ser excluído.");
    }
}
