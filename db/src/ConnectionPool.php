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

namespace kuiper\db;

use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\pool\PoolInterface;

class ConnectionPool implements ConnectionPoolInterface
{
    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * @var ConnectionInterface[]
     */
    private $connections;

    public function __construct(PoolInterface $pool)
    {
        $this->pool = $pool;
    }

    public function take(): ConnectionInterface
    {
        if (isset($this->connections[$this->getCoroutineId()])) {
            return $this->connections[$this->getCoroutineId()];
        }

        return $this->connections[$this->getCoroutineId()] = $this->pool->take();
    }

    public function release(ConnectionInterface $connection): void
    {
        if ($connection->inTransaction()) {
            return;
        }
        unset($this->connections[$this->getCoroutineId()]);
        $this->pool->release($connection);
    }

    private function getCoroutineId(): int
    {
        return Coroutine::getCoroutineId();
    }
}
