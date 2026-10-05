<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Filament\Tenant\Concerns\RequiresFeature;
use Filament\Pages\Page;

/**
 * Página de um módulo fictício, só para provar o trait RequiresFeature.
 */
final class FeatureGatedPage extends Page
{
    use RequiresFeature;

    protected static function requiredFeature(): string
    {
        return 'helpdesk.tickets';
    }
}
