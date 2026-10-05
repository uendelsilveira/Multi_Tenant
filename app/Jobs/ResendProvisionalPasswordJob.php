<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Tenant\ResendProvisionalPasswordAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ResendProvisionalPasswordJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $tenantId,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(ResendProvisionalPasswordAction $action): void
    {
        $action->execute($this->tenantId);
    }
}
