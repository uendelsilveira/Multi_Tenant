<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Contracts\ProvisionalPasswordNotifierInterface;
use App\Enums\DomainPanel;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Notifications\ProvisionalPasswordNotification;
use Carbon\CarbonInterface;

final class MailProvisionalPasswordNotifier implements ProvisionalPasswordNotifierInterface
{
    public function send(TenantUser $admin, Tenant $tenant, string $plainPassword, CarbonInterface $expiresAt): void
    {
        $admin->notify(new ProvisionalPasswordNotification(
            loginUrl: $this->loginUrl($tenant),
            plainPassword: $plainPassword,
            expiresAt: $expiresAt,
        ));
    }

    private function loginUrl(Tenant $tenant): string
    {
        /** @var Domain|null $domain */
        $domain = $tenant->domains->first(fn (Domain $domain): bool => $domain->panel === DomainPanel::Admin)
            ?? $tenant->domains->first();

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $host = $domain !== null ? $domain->domain : $tenant->id;

        return "{$scheme}://{$host}/admin/login";
    }
}
