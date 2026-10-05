<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Jobs\Middleware\SkipWhenTenantIsSuspended;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Job de um módulo fictício, só para provar o middleware SkipWhenTenantIsSuspended.
 */
final class SuspensionGatedJob implements ShouldQueue
{
    use Queueable;

    public static int $handled = 0;

    /** @return list<object> */
    public function middleware(): array
    {
        return [new SkipWhenTenantIsSuspended];
    }

    public function handle(): void
    {
        self::$handled++;
    }
}
