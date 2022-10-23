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

interface DnsResolverInterface
{
    /**
     * @param string $hostname
     *
     * @return array
     */
    public function resolve(string $hostname): array;
}
