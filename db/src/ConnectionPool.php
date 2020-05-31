<?php

declare(strict_types=1);

namespace kuiper\db;

class ConnectionPool implements ConnectionPoolInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function take(): ConnectionInterface
    {
        return $this->connection;
    }

    public function release(ConnectionInterface $connection): void
    {
    }
}
