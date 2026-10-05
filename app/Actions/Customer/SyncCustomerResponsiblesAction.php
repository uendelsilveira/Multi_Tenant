<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Actions\BaseAction;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Log;

final class SyncCustomerResponsiblesAction extends BaseAction
{
    public function __construct(
        private readonly CustomerService $service,
    ) {}

    /** @param list<int> $userIds */
    public function execute(int $customerId, array $userIds): void
    {
        $this->service->syncResponsibles($customerId, $userIds);

        Log::info('customer.responsibles_changed', ['customer_id' => $customerId, 'user_ids' => $userIds]);
    }
}
