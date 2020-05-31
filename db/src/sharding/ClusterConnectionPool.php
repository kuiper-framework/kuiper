<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use kuiper\db\ConnectionInterface;
use kuiper\db\ConnectionPoolInterface;

class ClusterConnectionPool implements ConnectionPoolInterface
{
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
        $this->connectionId = $connectionId;
    }

    public function take(): ConnectionInterface
    {
        return $this->poolList[$this->connectionId]->take();
    }

    public function release(ConnectionInterface $connection): void
    {
        $this->poolList[$this->connectionId]->release($connection);
    }
}
