<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\TenantUser\IssueTenantUserProvisionalPasswordAction;
use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

/**
 * É despachado de dentro do contexto de um tenant, e o pacote de tenancy o
 * executa no mesmo tenant. Leva só o id da pessoa: a senha é gerada aqui, no
 * momento do envio, e nunca passa pela fila.
 */
final class IssueTenantUserProvisionalPasswordJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $userId,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(IssueTenantUserProvisionalPasswordAction $action): void
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('IssueTenantUserProvisionalPasswordJob precisa rodar no contexto de um tenant.');
        }

        $action->execute($this->userId, $tenant);
    }
}
