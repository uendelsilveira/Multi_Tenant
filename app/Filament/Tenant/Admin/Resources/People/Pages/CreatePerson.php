<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\People\Pages;

use App\Actions\TenantUser\CreateTenantUserAction;
use App\DTOs\TenantUser\CreateTenantUserDTO;
use App\Exceptions\DomainException;
use App\Filament\Tenant\Admin\Resources\People\PersonResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class CreatePerson extends CreateRecord
{
    protected static string $resource = PersonResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CreateTenantUserAction::class)->execute(CreateTenantUserDTO::fromArray($data));
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): string
    {
        return 'Pessoa cadastrada. A senha provisória será enviada por e-mail.';
    }
}
