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
    /**
     * @var string
     */
    private $poolName;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(string $poolName, Connection $connection)
    {
        $this->connection = $connection;
        $this->poolName = $poolName;
    }

    public function getPoolName(): string
    {
        return $this->poolName;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
