<?php

declare(strict_types=1);

namespace App\Exceptions\Tenant;

use App\Exceptions\DomainException;

final class TenantAlreadyExistsException extends DomainException
{
    public static function withSlug(string $slug): self
    {
        return new self("O slug \"{$slug}\" já está em uso por outro tenant, ativo ou excluído.");
    }

    public static function withDocument(string $document): self
    {
        return new self("O documento {$document} já está cadastrado em outro tenant.");
    }
}
