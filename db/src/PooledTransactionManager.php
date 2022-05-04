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

class PooledTransactionManager implements TransactionManagerInterface
{
    public function __construct(private readonly ConnectionPoolInterface $connectionPool)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $callback): mixed
    {
        $connection = $this->connectionPool->take();
        try {
            return (new TransactionManager($connection))->transaction($callback);
        } finally {
            $this->connectionPool->release($connection);
        }
    }
}
