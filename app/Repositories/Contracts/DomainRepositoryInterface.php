<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Domain;

interface DomainRepositoryInterface
{
    public function find(int $id): ?Domain;

    public function findByHost(string $host): ?Domain;

    public function markVerified(Domain $domain, int $centralUserId): void;
}
