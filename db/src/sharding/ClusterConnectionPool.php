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

namespace kuiper\db\sharding;

use InvalidArgumentException;
use kuiper\db\ConnectionInterface;
use kuiper\db\ConnectionPoolInterface;

class ClusterConnectionPool implements ConnectionPoolInterface
{
    private int $connectionId = -1;

    public function __construct(private readonly array $poolList)
    {
    }

    public function hasConnection(): bool
    {
        return $this->connectionId >= 0;
    }

    public function getConnectionId(): int
    {
        if (!$this->hasConnection()) {
            throw new InvalidArgumentException('Connection id not set yet');
        }

        return $this->connectionId;
    }

    public function setConnectionId(int $connectionId): void
    {
        if ($connectionId < 0 || $connectionId >= count($this->poolList)) {
            throw new InvalidArgumentException("invalid connection id $connectionId");
        }
        $this->connectionId = $connectionId;
    }

    public function take(): ConnectionInterface
    {
        if (!$this->hasConnection()) {
            throw new InvalidArgumentException('connection id not set');
        }

        return $this->poolList[$this->connectionId]->take();
    }

    public function release(ConnectionInterface $connection): void
    {
        $this->poolList[$this->connectionId]->release($connection);
    }
}
