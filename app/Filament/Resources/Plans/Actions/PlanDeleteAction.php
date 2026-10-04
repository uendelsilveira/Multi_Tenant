<?php

declare(strict_types=1);

namespace App\Filament\Resources\Plans\Actions;

use App\Actions\Plan\DeletePlanAction;
use App\Exceptions\DomainException;
use App\Models\Plan;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;

final class PlanDeleteAction
{
    public static function make(): DeleteAction
    {
        return DeleteAction::make()
            ->using(function (Plan $record): bool {
                try {
                    app(DeletePlanAction::class)->execute($record->id);

                    return true;
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return false;
                }
            });
    }
}
