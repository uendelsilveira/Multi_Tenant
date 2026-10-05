<?php

declare(strict_types=1);

namespace App\Actions\TenantDomain;

use App\Actions\BaseAction;
use App\Services\TenantDomainService;

/**
 * Consulta síncrona de propósito: é uma conferência interativa, em que o
 * usuário central espera a resposta na tela para decidir se verifica o domínio.
 */
final class CheckTenantDomainDnsAction extends BaseAction
{
    public function __construct(
        private readonly TenantDomainService $service,
    ) {}

    /** @return list<string> */
    public function execute(int $domainId): array
    {
        return $this->service->dnsRecords($domainId);
    }
}
