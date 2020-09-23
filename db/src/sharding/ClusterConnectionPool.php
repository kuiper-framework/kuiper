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
     * @var int|null
     */
    private $connectionId;

    public function __construct(array $poolList)
    {
        $this->poolList = $poolList;
    }

    public function hasConnection(): bool
    {
        return isset($this->connectionId);
    }

    public function getConnectionId(): ?int
    {
        return $this->connectionId;
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
}
