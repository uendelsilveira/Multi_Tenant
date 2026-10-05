<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Roles\Pages;

use App\Actions\Role\CreateRoleAction;
use App\DTOs\Role\CreateRoleDTO;
use App\Exceptions\DomainException;
use App\Filament\Tenant\Admin\Resources\Roles\RoleResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CreateRoleAction::class)->execute(CreateRoleDTO::fromArray($data));
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            throw new Halt;
        }
    }
}
