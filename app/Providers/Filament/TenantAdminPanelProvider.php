<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\DomainPanel;
use Filament\Support\Colors\Color;

final class TenantAdminPanelProvider extends TenantPanelProvider
{
    protected function domainPanel(): DomainPanel
    {
        return DomainPanel::Admin;
    }

    protected function directory(): string
    {
        return 'Admin';
    }

    protected function primaryColor(): array
    {
        return Color::Amber;
    }
}
