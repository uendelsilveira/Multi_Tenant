<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DomainPanel;
use App\Enums\DomainStatus;
use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

/**
 * @property int $id
 * @property string $domain
 * @property string $tenant_id
 * @property DomainPanel $panel
 * @property DomainStatus $status
 */
final class Domain extends BaseDomain
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'panel' => DomainPanel::class,
            'status' => DomainStatus::class,
        ];
    }
}
