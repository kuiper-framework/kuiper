<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery\dns;

interface DnsResolverInterface
{
    /**
     * @param string $hostname
     *
     * @return array
     */
    public function resolve(string $hostname): array;
}
