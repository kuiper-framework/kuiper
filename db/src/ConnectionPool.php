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

use kuiper\swoole\pool\PoolInterface;

class ConnectionPool implements ConnectionPoolInterface
{
    /**
     * @var PoolInterface
     */
    private $pool;

    public function __construct(PoolInterface $pool)
    {
        $this->pool = $pool;
    }

    public function take(): ConnectionInterface
    {
        return $this->pool->take();
    }
}
