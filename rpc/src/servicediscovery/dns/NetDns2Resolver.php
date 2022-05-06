<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery\dns;

use Net_DNS2_Resolver;
use Net_DNS2_RR_SRV;

class NetDns2Resolver implements DnsResolverInterface
{
    public function __construct(private readonly Net_DNS2_Resolver $resolver)
    {
    }

    /**
     * @throws \Net_DNS2_Exception
     */
    public function resolve(string $hostname): array
    {
        $result = $this->resolver->query($hostname, 'SRV');

        return array_map(static function (Net_DNS2_RR_SRV $record) {
            return [
                'target' => $record->target,
                'port' => $record->port,
                'weight' => $record->weight,
                'ttl' => $record->ttl,
                'pri' => $record->priority,
                'class' => $record->class,
                'type' => $record->type,
            ];
        }, $result->answer);
    }
}
