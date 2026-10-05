<?php

declare(strict_types=1);

namespace App\Exceptions\Tenant;

use App\Exceptions\DomainException;

final class ProvisionalPasswordException extends DomainException
{
    public static function tenantNotReady(string $tenantId): self
    {
        return new self("O tenant [{$tenantId}] ainda não está pronto. Aguarde o provisionamento terminar.");
    }

    public static function adminNotFound(string $tenantId): self
    {
        return new self("O tenant [{$tenantId}] não tem admin inicial.");
    }

    public static function alreadyUsed(): self
    {
        return new self('O admin já fez o primeiro acesso. A partir daí a recuperação é pelo "esqueci minha senha" do próprio painel.');
    }

    public static function notPending(): self
    {
        return new self('Este usuário não tem senha provisória pendente de troca.');
    }

    public static function expired(): self
    {
        return new self('A senha provisória expirou. Peça o reenvio a quem cadastrou sua empresa.');
    }
}
