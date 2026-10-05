<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Billing\EnforceGracePeriodAction;
use Illuminate\Console\Command;

final class EnforceGracePeriodCommand extends Command
{
    protected $signature = 'billing:enforce-grace';

    protected $description = 'Suspende os tenants com pagamento vencido há mais tempo que a carência';

    public function handle(EnforceGracePeriodAction $action): int
    {
        $suspended = $action->execute();

        $this->info(count($suspended).' tenant(s) suspenso(s) por falta de pagamento.');

        return self::SUCCESS;
    }
}
