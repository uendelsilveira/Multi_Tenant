<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Base de toda violação de regra de negócio. A mensagem é exibida ao usuário.
 */
abstract class DomainException extends RuntimeException {}
