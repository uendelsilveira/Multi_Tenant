<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\DomainPanel;
use Filament\Support\Colors\Color;

final class TenantCustomerPanelProvider extends TenantPanelProvider
{
    protected function domainPanel(): DomainPanel
    {
        return DomainPanel::Customer;
    }

    protected function directory(): string
    {
        return 'Customer';
    }

    protected function primaryColor(): array
    {
        return Color::Emerald;
    }
}
