<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use kuiper\db\ConnectionInterface;
use kuiper\db\ConnectionPoolInterface;
use kuiper\swoole\pool\PoolTrait;

class ClusterConnectionPool implements ConnectionPoolInterface
{
    use PoolTrait;

    /**
     * @var ConnectionPoolInterface[]
     */
    private $poolList;

    /**
     * @var int
     */
    private $connectionId;

    public function __construct(array $poolList)
    {
        $this->poolList = $poolList;
    }

    public function setConnectionId(int $connectionId): void
    {
        if ($connectionId < 0 || $connectionId >= count($this->poolList)) {
            throw new \InvalidArgumentException("invalid connection id $connectionId");
        }
        $this->connectionId = $connectionId;
    }

    public function take(): ConnectionInterface
    {
        if (!isset($this->connectionId)) {
            throw new \InvalidArgumentException('connection id not set');
        }

        return $this->poolList[$this->connectionId]->take();
    }

    public function release(ConnectionInterface $connection): void
    {
        if (!isset($this->connectionId)) {
            throw new \InvalidArgumentException('connection id not set');
        }
        $this->poolList[$this->connectionId]->release($connection);
    }
}
