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
            loginUrl: $this->loginUrl($admin, $tenant),
            plainPassword: $plainPassword,
            expiresAt: $expiresAt,
        ));
    }

    private function loginUrl(TenantUser $admin, Tenant $tenant): string
    {
        $panel = $admin->type?->panel() ?? DomainPanel::Admin;

        /** @var Domain|null $domain */
        $domain = $tenant->domains->first(fn (Domain $domain): bool => $domain->panel === $panel)
            ?? $tenant->domains->first();

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $host = $domain !== null ? $domain->domain : $tenant->id;

        $path = ($domain !== null ? $domain->panel : DomainPanel::Admin)->path();

        return "{$scheme}://{$host}/{$path}/login";
    }
}
