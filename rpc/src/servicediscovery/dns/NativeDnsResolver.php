<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


declare(strict_types=1);

namespace kuiper\rpc\servicediscovery\dns;

class NativeDnsResolver implements DnsResolverInterface
{
    public function resolve(string $hostname): array
    {
        return dns_get_record($hostname, DNS_SRV);
    }
}
