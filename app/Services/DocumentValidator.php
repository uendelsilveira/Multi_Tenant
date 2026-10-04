<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PersonType;

/**
 * Valida CPF e CNPJ pelos dígitos verificadores.
 * O CNPJ aceita o formato alfanumérico (12 posições de letras ou números + 2 dígitos).
 */
final class DocumentValidator
{
    public function isValid(PersonType $type, string $document): bool
    {
        return match ($type) {
            PersonType::Individual => $this->isValidCpf($document),
            PersonType::Company => $this->isValidCnpj($document),
        };
    }

    private function isValidCpf(string $cpf): bool
    {
        if (preg_match('/^\d{11}$/', $cpf) !== 1 || preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($length = 9; $length <= 10; $length++) {
            $sum = 0;

            for ($i = 0; $i < $length; $i++) {
                $sum += ((int) $cpf[$i]) * ($length + 1 - $i);
            }

            if ((int) $cpf[$length] !== ($sum * 10) % 11 % 10) {
                return false;
            }
        }

        return true;
    }

    private function isValidCnpj(string $cnpj): bool
    {
        if (preg_match('/^[0-9A-Z]{12}\d{2}$/', $cnpj) !== 1 || preg_match('/^(.)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($length = 12; $length <= 13; $length++) {
            $sum = 0;
            $offset = 13 - $length;

            for ($i = 0; $i < $length; $i++) {
                $sum += (ord($cnpj[$i]) - 48) * $weights[$i + $offset];
            }

            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $cnpj[$length] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
