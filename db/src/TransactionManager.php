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

class TransactionManager implements TransactionManagerInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $callback)
    {
        $connection = $this->connection;
        if ($connection->inTransaction()) {
            return $callback($connection);
        }
        try {
            $connection->beginTransaction();
            $ret = $callback($connection);
            $connection->commit();

            return $ret;
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
