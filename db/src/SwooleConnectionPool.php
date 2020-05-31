<?php

declare(strict_types=1);

namespace kuiper\db;

use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\pool\PoolConfig;
use kuiper\swoole\pool\PoolInterface;
use kuiper\swoole\pool\SimplePool;

class SwooleConnectionPool implements ConnectionPoolInterface
{
    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * @var ConnectionInterface[]
     */
    private $connections;

    public function __construct(PoolConfig $poolConfig, $dsn, $username = null, $password = null, array $options = [], array $attributes = [])
    {
        $this->pool = new SimplePool(static function () use ($dsn, $username, $password, $options, $attributes) {
            return new Connection($dsn, $username, $password, $options, $attributes);
        }, $poolConfig);
    }

    public function take(): ConnectionInterface
    {
        $coroutineId = Coroutine::getCoroutineId();
        if (isset($this->connections[$coroutineId])) {
            $this->connections[$coroutineId] = $this->pool->take();
        }

        return $this->connections[$coroutineId];
    }

    public function release(ConnectionInterface $connection): void
    {
        if ($connection->inTransaction()) {
            return;
        }
        $this->pool->release($connection);
        unset($this->connections[Coroutine::getCoroutineId()]);
    }
}
