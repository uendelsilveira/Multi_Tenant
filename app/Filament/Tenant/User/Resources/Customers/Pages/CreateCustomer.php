<?php

declare(strict_types=1);

namespace App\Filament\Tenant\User\Resources\Customers\Pages;

use App\Actions\Customer\CreateCustomerAction;
use App\DTOs\Customer\CreateCustomerDTO;
use App\Exceptions\DomainException;
use App\Filament\Tenant\Shared\Customers\CurrentPerson;
use App\Filament\Tenant\User\Resources\Customers\CustomerResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

final class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CreateCustomerAction::class)->execute(CreateCustomerDTO::fromArray($data), CurrentPerson::get());
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->persistent()->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): string
    {
        return 'Cliente cadastrado. O acesso ao portal será enviado por e-mail.';
    }
}
