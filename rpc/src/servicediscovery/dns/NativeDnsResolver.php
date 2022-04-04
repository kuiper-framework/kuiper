<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery\dns;

class NativeDnsResolver implements DnsResolverInterface
{
    public function resolve(string $hostname): array
    {
        return dns_get_record($hostname, DNS_SRV);
    }
}
