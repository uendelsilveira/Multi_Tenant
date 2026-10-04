<?php

declare(strict_types=1);

namespace App\Exceptions\Tenant;

use App\Exceptions\DomainException;

final class InvalidTenantDataException extends DomainException
{
    public static function slugFormat(string $slug): self
    {
        return new self("O slug \"{$slug}\" é inválido. Use de 3 a 50 caracteres: letras minúsculas, números e hífen, começando por letra.");
    }

    public static function document(string $document): self
    {
        return new self("O documento {$document} não é um CPF ou CNPJ válido.");
    }
}
