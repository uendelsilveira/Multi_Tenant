<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Tenant;
use Closure;

/**
 * Infraestrutura do ambiente de um tenant: o banco dele e a troca de contexto.
 * Isola o pacote de tenancy das regras de provisionamento.
 */
interface TenantEnvironmentInterface
{
    /** Cria o banco do tenant se ainda não existir. Pode ser chamado mais de uma vez. */
    public function ensureDatabaseExists(Tenant $tenant): void;

    /** Roda as migrations pendentes no banco do tenant. Pode ser chamado mais de uma vez. */
    public function migrate(Tenant $tenant): void;

    /**
     * Executa o callback dentro do contexto do tenant e volta ao contexto anterior.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Tenant $tenant, Closure $callback): mixed;
}
