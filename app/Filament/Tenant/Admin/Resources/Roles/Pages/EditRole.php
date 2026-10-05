<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Roles\Pages;

use App\Actions\Role\UpdateRoleAction;
use App\DTOs\Role\UpdateRoleDTO;
use App\Exceptions\DomainException;
use App\Filament\Tenant\Admin\Resources\Roles\Actions\RoleDeleteAction;
use App\Filament\Tenant\Admin\Resources\Roles\RoleResource;
use App\Models\Role;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    /** @return array<int, DeleteAction> */
    protected function getHeaderActions(): array
    {
        return [
            RoleDeleteAction::make(),
        ];
    }

    /**
     * Só transformação de formato: o tipo base vai para o campo como texto.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $role = $this->getRecord();

        if ($role instanceof Role) {
            $data['base_type'] = $role->base_type?->value;
            $data['permissions'] = $role->permissions ?? [];
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(UpdateRoleAction::class)->execute(UpdateRoleDTO::fromArray((int) $record->getKey(), $data));
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            throw new Halt;
        }
    }
}
