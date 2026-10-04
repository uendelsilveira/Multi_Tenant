<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Pages;

use App\Actions\Tenant\UpdateTenantAction;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Exceptions\DomainException;
use App\Filament\Resources\Tenants\Actions\TenantRestoreAction;
use App\Filament\Resources\Tenants\Actions\TenantSoftDeleteAction;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Domain;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            TenantSoftDeleteAction::make(),
            TenantRestoreAction::make(),
        ];
    }

    /**
     * Só transformação de formato: leva os domínios do tenant para o repeater.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $tenant = $this->getRecord();

        if ($tenant instanceof Tenant) {
            $data['domains'] = $tenant->domains
                ->map(fn (Domain $domain): array => [
                    'domain' => $domain->domain,
                    'panel' => $domain->panel->value,
                ])
                ->values()
                ->all();
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(UpdateTenantAction::class)->execute(UpdateTenantDTO::fromArray((string) $record->getKey(), $data));
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            throw new Halt;
        }
    }
}
