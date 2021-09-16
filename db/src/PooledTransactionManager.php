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
    /**
     * @var ConnectionPoolInterface
     */
    private $connectionPool;

    public function __construct(ConnectionPoolInterface $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $callback)
    {
        $manager = new TransactionManager($this->connectionPool->take());

        return $manager->transaction($callback);
    }
}
