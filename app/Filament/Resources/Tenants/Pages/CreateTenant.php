<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Pages;

use App\Actions\Tenant\CreateTenantAction;
use App\DTOs\Tenant\CreateTenantDTO;
use App\Exceptions\DomainException;
use App\Filament\Resources\Tenants\TenantResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CreateTenantAction::class)->execute(CreateTenantDTO::fromArray($data));
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            throw new Halt;
        }
    }
}
