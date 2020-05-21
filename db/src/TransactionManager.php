<?php

declare(strict_types=1);

namespace kuiper\db;

class TransactionManager implements TransactionManagerInterface
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $callback)
    {
        if ($this->connection->inTransaction()) {
            return $callback($this->connection);
        }
        try {
            $this->connection->beginTransaction();
            $ret = $callback($this->connection);
            $this->connection->commit();

            return $ret;
        } catch (\Throwable $e) {
            $this->connection->rollback();
            throw $e;
        }
    }
}
