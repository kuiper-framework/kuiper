<?php

declare(strict_types=1);

namespace kuiper\db;

class TransactionManager implements TransactionManagerInterface
{
    /**
     * @var ConnectionPoolInterface
     */
    protected $pool;

    public function __construct(ConnectionPoolInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $callback)
    {
        $connection = $this->pool->take();
        if ($connection->inTransaction()) {
            return $callback($connection);
        }
        try {
            $connection->beginTransaction();
            $ret = $callback($connection);
            $connection->commit();

            return $ret;
        } catch (\Throwable $e) {
            $connection->rollback();
            throw $e;
        }
    }
}
