<?php

declare(strict_types=1);

namespace App\Exceptions\Tenant;

use App\Exceptions\DomainException;

final class InvalidTenantDomainsException extends DomainException
{
    public static function none(): self
    {
        return new self('Informe pelo menos um domínio para o tenant.');
    }

    public static function missingAdminPanel(): self
    {
        return new self('Pelo menos um domínio precisa apontar para o painel Admin.');
    }

    public static function invalidHost(string $host): self
    {
        return new self("\"{$host}\" não é um domínio válido. Informe o endereço completo, sem http:// e sem caminho.");
    }

    public static function duplicated(string $host): self
    {
        return new self("O domínio {$host} foi informado mais de uma vez.");
    }

    public static function central(string $host): self
    {
        return new self("O domínio {$host} pertence ao painel central e não pode ser usado por um tenant.");
    }

    public static function alreadyInUse(string $host): self
    {
        return new self("O domínio {$host} já está cadastrado em outro tenant.");
    }
}
