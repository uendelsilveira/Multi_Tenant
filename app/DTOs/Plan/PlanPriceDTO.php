<?php

declare(strict_types=1);

namespace App\DTOs\Plan;

use App\Enums\BillingCycle;

final class PlanPriceDTO
{
    public function __construct(
        public readonly BillingCycle $cycle,
        public readonly string $price,
    ) {}

    /**
     * Converte o mapa ciclo => preço do formulário. Ciclo sem preço é ignorado.
     *
     * @param  array<string, mixed>  $prices
     * @return list<self>
     */
    public static function listFromArray(array $prices): array
    {
        $list = [];

        foreach ($prices as $cycle => $price) {
            if ($price === null || $price === '') {
                continue;
            }

            $list[] = new self(BillingCycle::from((string) $cycle), (string) $price);
        }

        return $list;
    }
}
