<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Actions\BaseAction;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Log;

final class SetCustomerActiveAction extends BaseAction
{
    public function __construct(
        private readonly CustomerService $service,
    ) {}

    public function execute(int $customerId, bool $active): void
    {
        $this->service->setActive($customerId, $active);

        Log::info($active ? 'customer.activated' : 'customer.deactivated', ['customer_id' => $customerId]);
    }
}
