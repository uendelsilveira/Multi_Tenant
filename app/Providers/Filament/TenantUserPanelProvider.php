<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\DomainPanel;
use Filament\Support\Colors\Color;

final class TenantUserPanelProvider extends TenantPanelProvider
{
    protected function domainPanel(): DomainPanel
    {
        return DomainPanel::User;
    }

    protected function directory(): string
    {
        return 'User';
    }

    protected function primaryColor(): array
    {
        return Color::Sky;
    }
}
