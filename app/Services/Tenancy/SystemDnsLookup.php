<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Contracts\DnsLookupInterface;

final class SystemDnsLookup implements DnsLookupInterface
{
    public function lookup(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA | DNS_CNAME);

        if ($records === false) {
            return [];
        }

        $lines = [];

        foreach ($records as $record) {
            $type = (string) ($record['type'] ?? '');
            $value = (string) ($record['ip'] ?? $record['ipv6'] ?? $record['target'] ?? '');

            if ($type !== '' && $value !== '') {
                $lines[] = "{$type} {$value}";
            }
        }

        return $lines;
    }
}
