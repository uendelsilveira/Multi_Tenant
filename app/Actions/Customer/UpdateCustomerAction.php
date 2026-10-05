<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Actions\BaseAction;
use App\DTOs\Customer\UpdateCustomerDTO;
use App\Models\Customer;
use App\Models\TenantUser;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Log;

final class UpdateCustomerAction extends BaseAction
{
    public function __construct(
        private readonly CustomerService $service,
    ) {}

    public function execute(UpdateCustomerDTO $dto, TenantUser $actor): Customer
    {
        $customer = $this->service->update($dto, $actor);

        Log::info('customer.updated', ['customer_id' => $customer->id, 'actor_id' => $actor->id]);

        return $customer;
    }
}
