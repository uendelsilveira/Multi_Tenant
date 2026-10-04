<?php

declare(strict_types=1);

namespace App\Filament\Resources\Plans\Pages;

use App\Actions\Plan\UpdatePlanAction;
use App\DTOs\Plan\UpdatePlanDTO;
use App\Exceptions\DomainException;
use App\Filament\Resources\Plans\Actions\PlanDeleteAction;
use App\Filament\Resources\Plans\PlanResource;
use App\Models\Plan;
use App\Models\PlanPrice;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    /** @return array<int, DeleteAction> */
    protected function getHeaderActions(): array
    {
        return [
            PlanDeleteAction::make(),
        ];
    }

    /**
     * Só transformação de formato: leva preços e funcionalidades do plano
     * para os campos do formulário.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $plan = $this->getRecord();

        if ($plan instanceof Plan) {
            $data['prices'] = $plan->prices
                ->mapWithKeys(fn (PlanPrice $price): array => [$price->billing_cycle->value => $price->price])
                ->all();
            $data['feature_ids'] = $plan->features->modelKeys();
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(UpdatePlanAction::class)->execute(UpdatePlanDTO::fromArray((int) $record->getKey(), $data));
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();

            throw new Halt;
        }
    }
}
