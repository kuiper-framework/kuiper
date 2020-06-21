<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

interface PoolFactoryInterface
{
    /**
     * Create pool.
     */
    public function create(string $poolName, callable $connectionFactory): PoolInterface;
}
