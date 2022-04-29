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

namespace kuiper\swoole\pool;

use kuiper\event\StoppableEventTrait;
use Psr\EventDispatcher\StoppableEventInterface;

class ConnectionCreateEvent implements StoppableEventInterface
{
    use StoppableEventTrait;

    public function __construct(
        private readonly string $poolName,
        private readonly ConnectionInterface $connection)
    {
    }

    public function getPoolName(): string
    {
        return $this->poolName;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}
