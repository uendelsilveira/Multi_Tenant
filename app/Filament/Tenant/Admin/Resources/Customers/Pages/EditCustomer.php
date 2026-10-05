<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Admin\Resources\Customers\Pages;

use App\Actions\Customer\UpdateCustomerAction;
use App\DTOs\Customer\UpdateCustomerDTO;
use App\Exceptions\DomainException;
use App\Filament\Tenant\Admin\Resources\Customers\CustomerResource;
use App\Filament\Tenant\Shared\Customers\CurrentPerson;
use App\Filament\Tenant\Shared\Customers\CustomerFormData;
use App\Models\Customer;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    /** Cliente não é excluído; desativar se faz pela listagem. */
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $customer = $this->getRecord();

        return $customer instanceof Customer ? CustomerFormData::fill($data, $customer) : $data;
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(UpdateCustomerAction::class)->execute(
                UpdateCustomerDTO::fromArray((int) $record->getKey(), $data),
                CurrentPerson::get(),
            );
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->persistent()->send();

            throw new Halt;
        }
    }
}
