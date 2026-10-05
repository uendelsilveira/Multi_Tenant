<?php

declare(strict_types=1);

namespace App\Exceptions\Customer;

use App\Exceptions\DomainException;

final class CustomerRuleException extends DomainException
{
    public static function onlyUsersRegister(): self
    {
        return new self('Clientes são cadastrados pelos usuários. O admin gerencia os vínculos.');
    }

    public static function emailTaken(string $email): self
    {
        return new self("O e-mail {$email} já está cadastrado na empresa. Se for um cliente de outro usuário, peça ao admin para vincular você a ele.");
    }

    public static function invalidDocument(string $document): self
    {
        return new self("O documento {$document} não é um CPF ou CNPJ válido.");
    }

    public static function notAccessible(): self
    {
        return new self('Este cliente não está vinculado a você.');
    }

    public static function needsResponsible(): self
    {
        return new self('Todo cliente precisa de pelo menos um usuário responsável.');
    }

    public static function invalidResponsible(): self
    {
        return new self('Só usuários ativos podem ser responsáveis por um cliente.');
    }
}
