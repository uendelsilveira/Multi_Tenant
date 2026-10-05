<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Actions\BaseAction;
use App\DTOs\Customer\CreateCustomerDTO;
use App\Events\TenantUser\TenantUserAccessRequested;
use App\Models\Customer;
use App\Models\TenantUser;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Log;

final class CreateCustomerAction extends BaseAction
{
    public function __construct(
        private readonly CustomerService $service,
    ) {}

    public function execute(CreateCustomerDTO $dto, TenantUser $actor): Customer
    {
        $customer = $this->service->create($dto, $actor);

        // Todo cliente recebe acesso ao portal: a senha provisória é emitida em fila.
        event(new TenantUserAccessRequested($customer->id));

        Log::info('customer.created', ['customer_id' => $customer->id, 'registered_by' => $actor->id]);

        return $customer;
    }
}
