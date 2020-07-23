<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

use kuiper\event\StoppableEventTrait;
use Psr\EventDispatcher\StoppableEventInterface;

class ConnectionReleaseEvent implements StoppableEventInterface
{
    use StoppableEventTrait;
    /**
     * @var string
     */
    private $poolName;

    private $connection;

    public function __construct(string $poolName, $connection)
    {
        $this->connection = $connection;
        $this->poolName = $poolName;
    }

    public function getPoolName(): string
    {
        return $this->poolName;
    }

    /**
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
