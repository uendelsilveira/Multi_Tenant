<?php

declare(strict_types=1);

namespace Tests\Support;

use App\DTOs\Tenant\CreateTenantDTO;
use App\DTOs\Tenant\TenantCompanyDTO;
use App\DTOs\Tenant\TenantDomainDTO;
use App\DTOs\Tenant\UpdateTenantDTO;
use App\Enums\BillingCycle;
use App\Enums\DomainPanel;
use App\Enums\PaymentGateway;
use App\Enums\PersonType;

/**
 * Dados válidos de tenant para os testes. Cada teste altera só o que quer provar.
 */
final class TenantData
{
    public const VALID_CNPJ = '11222333000181';

    public const VALID_ALPHANUMERIC_CNPJ = '12ABC34501DE35';

    public const VALID_CPF = '52998224725';

    public static function company(string $document = self::VALID_CNPJ, PersonType $type = PersonType::Company): TenantCompanyDTO
    {
        return new TenantCompanyDTO(
            legalName: 'Acme Ltda',
            tradeName: 'Acme',
            personType: $type,
            document: $document,
            stateRegistration: null,
            contactName: 'Ana Souza',
            contactEmail: 'ana@acme.test',
            contactPhone: '51999990000',
            zipCode: '90000000',
            street: 'Rua A',
            number: '10',
            complement: null,
            district: 'Centro',
            city: 'Porto Alegre',
            state: 'RS',
            notes: null,
        );
    }

    /** @param list<TenantDomainDTO>|null $domains */
    public static function create(
        string $slug = 'acme',
        int $planId = 1,
        BillingCycle $cycle = BillingCycle::Monthly,
        ?array $domains = null,
        ?TenantCompanyDTO $company = null,
        PaymentGateway $gateway = PaymentGateway::Asaas,
    ): CreateTenantDTO {
        return new CreateTenantDTO(
            slug: $slug,
            company: $company ?? self::company(),
            planId: $planId,
            billingCycle: $cycle,
            gateway: $gateway,
            domains: $domains ?? [new TenantDomainDTO('painel.acme.test', DomainPanel::Admin)],
        );
    }

    /** @param list<TenantDomainDTO>|null $domains */
    public static function update(
        string $tenantId = 'acme',
        int $planId = 1,
        BillingCycle $cycle = BillingCycle::Monthly,
        ?array $domains = null,
    ): UpdateTenantDTO {
        return new UpdateTenantDTO(
            tenantId: $tenantId,
            company: self::company(),
            planId: $planId,
            billingCycle: $cycle,
            domains: $domains ?? [new TenantDomainDTO('painel.acme.test', DomainPanel::Admin)],
        );
    }
}
