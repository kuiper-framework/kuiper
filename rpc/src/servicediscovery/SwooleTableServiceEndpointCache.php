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

namespace kuiper\rpc\servicediscovery;

use kuiper\cache\SwooleTableCache;

/**
 * 存储服务地址
 */
class SwooleTableServiceEndpointCache extends SwooleTableCache
{
    public const KEY_DATA = 'data';
    public const KEY_EXPIRES = 'expires';

    public function __construct(int $ttl = 60, int $capacity = 256, int $size = 2046)
    {
        parent::__construct($ttl, $capacity, $size);
    }

    /**
     * {@inheritDoc}
     */
    protected function serialize($value): string
    {
        return (string) $value;
    }

    /**
     * {@inheritDoc}
     */
    protected function unserialize(string $data)
    {
        return ServiceEndpoint::fromString($data);
    }
}
