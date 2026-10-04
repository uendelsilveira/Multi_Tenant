<?php

declare(strict_types=1);

namespace App\Filament\Resources\Plans\Pages;

use App\Actions\Plan\CreatePlanAction;
use App\DTOs\Plan\CreatePlanDTO;
use App\Exceptions\DomainException;
use App\Filament\Resources\Plans\PlanResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CreatePlanAction::class)->execute(CreatePlanDTO::fromArray($data));
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            throw new Halt;
        }
    }
}
