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

use kuiper\event\InMemoryEventDispatcher;
use PHPUnit\Framework\TestCase;

class SimplePoolTest extends TestCase
{
    private array $connections;

    protected function setUp(): void
    {
        $this->connections = [];
    }

    public function testReturnReference(): void
    {
        $eventDispatcher = new InMemoryEventDispatcher();
        $pool = new SimplePool(
            'test',
            function ($connectionId, &$connection) {
                $connection = time();
                error_log("create connection $connectionId, $connection");
                $this->connections[$connectionId] = &$connection;

                return $connection;
            },
            new PoolConfig(),
            $eventDispatcher,
        );
        $connection = $pool->take();
        foreach ($this->connections as $i => $c) {
            $this->connections[$i] = null;
        }
        $pool->release($connection);
        $connection = $pool->take();
        $pool->release($connection);
        $events = $eventDispatcher->getEvents();
        $this->assertCount(1, $events);
    }

    public function testReturnConnection(): void
    {
        $eventDispatcher = new InMemoryEventDispatcher();
        $pool = new SimplePool(
            'test',
            function ($connectionId) {
                error_log("create connection $connectionId");

                return time();
            },
            new PoolConfig(),
            $eventDispatcher
        );
        $connection = $pool->take();
        foreach ($this->connections as $i => $c) {
            $this->connections[$i] = null;
        }
        $pool->release($connection);
        $connection = $pool->take();
        $pool->release($connection);
        $events = $eventDispatcher->getEvents();
        $this->assertCount(1, $events);
    }
}
