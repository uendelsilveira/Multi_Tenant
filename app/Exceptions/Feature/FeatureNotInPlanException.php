<?php

declare(strict_types=1);

namespace App\Exceptions\Feature;

use App\Exceptions\DomainException;

final class FeatureNotInPlanException extends DomainException
{
    public static function forKey(string $key): self
    {
        return new self("A funcionalidade \"{$key}\" não faz parte do plano contratado.");
    }
}
