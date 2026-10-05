<?php

declare(strict_types=1);

namespace App\Contracts;

interface DnsLookupInterface
{
    /**
     * Registros A, AAAA e CNAME do host, já formatados para leitura.
     *
     * @return list<string>
     */
    public function lookup(string $host): array;
}
