<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\People\Pages;

use App\Actions\TenantUser\UpdateTenantUserAction;
use App\DTOs\TenantUser\UpdateTenantUserDTO;
use App\Exceptions\DomainException;
use App\Filament\Tenant\Admin\Resources\People\PersonResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class EditPerson extends EditRecord
{
    protected static string $resource = PersonResource::class;

    /** Pessoa não é excluída, só desativada, e isso se faz pela listagem. */
    protected function getHeaderActions(): array
    {
        return [];
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(UpdateTenantUserAction::class)->execute(UpdateTenantUserDTO::fromArray((int) $record->getKey(), $data));
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            throw new Halt;
        }
    }
}
