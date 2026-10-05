<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\DomainStatus;
use App\Models\Domain;
use App\Repositories\Contracts\DomainRepositoryInterface;

final class DomainRepository implements DomainRepositoryInterface
{
    public function find(int $id): ?Domain
    {
        return Domain::query()->find($id);
    }

    public function findByHost(string $host): ?Domain
    {
        return Domain::query()->where('domain', $host)->first();
    }

    public function markVerified(Domain $domain, int $centralUserId): void
    {
        $domain->update([
            'status' => DomainStatus::Active->value,
            'verified_at' => now(),
            'verified_by' => $centralUserId,
        ]);
    }
}
