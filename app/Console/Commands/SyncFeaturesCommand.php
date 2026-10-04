<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Feature\SyncFeatureCatalogAction;
use Illuminate\Console\Command;

final class SyncFeaturesCommand extends Command
{
    protected $signature = 'features:sync';

    protected $description = 'Sincroniza o catálogo de funcionalidades de config/features.php com o banco central';

    public function handle(SyncFeatureCatalogAction $action): int
    {
        $count = $action->execute((array) config('features.catalog', []));

        $this->info("Catálogo sincronizado: {$count} funcionalidade(s).");

        return self::SUCCESS;
    }
}
