<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\PersonType;
use App\Services\DocumentValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DocumentValidatorTest extends TestCase
{
    #[DataProvider('documents')]
    public function test_it_validates_check_digits(PersonType $type, string $document, bool $expected): void
    {
        $this->assertSame($expected, (new DocumentValidator)->isValid($type, $document));
    }

    /** @return array<string, array{PersonType, string, bool}> */
    public static function documents(): array
    {
        return [
            'cpf válido' => [PersonType::Individual, '52998224725', true],
            'cpf com dígito errado' => [PersonType::Individual, '52998224726', false],
            'cpf com dígitos repetidos' => [PersonType::Individual, '11111111111', false],
            'cpf com tamanho errado' => [PersonType::Individual, '5299822472', false],
            'cnpj numérico válido' => [PersonType::Company, '11222333000181', true],
            'cnpj numérico com dígito errado' => [PersonType::Company, '11222333000182', false],
            'cnpj alfanumérico válido' => [PersonType::Company, '12ABC34501DE35', true],
            'cnpj alfanumérico com dígito errado' => [PersonType::Company, '12ABC34501DE36', false],
            'cnpj com dígitos repetidos' => [PersonType::Company, '00000000000000', false],
            'cpf informado como cnpj' => [PersonType::Company, '52998224725', false],
        ];
    }
}
