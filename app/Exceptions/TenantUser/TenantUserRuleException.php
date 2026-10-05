<?php

declare(strict_types=1);

namespace App\Exceptions\TenantUser;

use App\Exceptions\DomainException;

final class TenantUserRuleException extends DomainException
{
    public static function emailTaken(string $email): self
    {
        return new self("O e-mail {$email} já está em uso por outra pessoa.");
    }

    public static function customerRole(): self
    {
        return new self('Clientes não são cadastrados por aqui. Escolha um perfil de tipo admin ou usuário.');
    }

    public static function cannotDeactivateSelf(): self
    {
        return new self('Você não pode desativar a própria conta.');
    }

    public static function inactive(): self
    {
        return new self('Esta pessoa está desativada. Reative-a antes de enviar um novo acesso.');
    }

    public static function lastPeopleManager(): self
    {
        return new self('Esta alteração deixaria o tenant sem ninguém ativo que possa gerenciar pessoas.');
    }
}
