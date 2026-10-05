<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Roles\Actions;

use App\Actions\Role\DeleteRoleAction;
use App\Exceptions\DomainException;
use App\Models\Role;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;

final class RoleDeleteAction
{
    public static function make(): DeleteAction
    {
        return DeleteAction::make()
            ->using(function (Role $record): bool {
                try {
                    app(DeleteRoleAction::class)->execute($record->id);

                    return true;
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return false;
                }
            });
    }
}
