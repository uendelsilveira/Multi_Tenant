<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Tenant\MarkTenantProvisioningFailedAction;
use App\Actions\Tenant\ProvisionTenantAction;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Roda no contexto central. A troca para o contexto do tenant acontece dentro
 * do serviço de provisionamento, só no trecho que cria o admin.
 */
final class ProvisionTenantJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** Tempo máximo, em segundos, que a trava de unicidade é mantida. */
    public int $uniqueFor = 300;

    public function __construct(
        public readonly string $tenantId,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30];
    }

    public function uniqueId(): string
    {
        return $this->tenantId;
    }

    public function handle(ProvisionTenantAction $action): void
    {
        $action->execute($this->tenantId);
    }

    public function failed(?Throwable $exception): void
    {
        app(MarkTenantProvisioningFailedAction::class)->execute(
            $this->tenantId,
            $exception?->getMessage() ?? 'Falha desconhecida no provisionamento.',
        );
    }
}
