<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Billing\SyncSubscriptionPriceAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Roda no contexto central: cobrança é dado do central.
 */
final class SyncSubscriptionPriceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public readonly string $tenantId,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(SyncSubscriptionPriceAction $action): void
    {
        $action->execute($this->tenantId);
    }
}
