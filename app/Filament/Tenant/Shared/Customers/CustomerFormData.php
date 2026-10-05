<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Shared\Customers;

use App\Models\Customer;

/**
 * Só transformação de formato: leva os dados de cliente para os campos do formulário.
 */
final class CustomerFormData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fill(array $data, Customer $customer): array
    {
        $data['phone'] = $customer->profile?->phone;
        $data['document'] = $customer->profile?->document;
        $data['notes'] = $customer->profile?->notes;

        return $data;
    }
}
