<?php

declare(strict_types=1);

namespace kuiper\db;

use kuiper\swoole\pool\PoolInterface;

class ConnectionPool implements ConnectionPoolInterface
{
    /**
     * @var PoolInterface
     */
    private $pool;

    public function __construct(PoolInterface $pool)
    {
        $this->pool = $pool;
    }

    public function take(): ConnectionInterface
    {
        return $this->pool->take();
    }
}
