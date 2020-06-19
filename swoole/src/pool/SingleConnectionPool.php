<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

class SingleConnectionPool implements PoolInterface
{
    /**
     * @var object
     */
    private $connection;

    /**
     * SingleConnectionPool constructor.
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function take()
    {
        return $this->connection;
    }

    public function release($connection): void
    {
    }

    public function with(callable $callback)
    {
        return $callback($this->connection);
    }
}
