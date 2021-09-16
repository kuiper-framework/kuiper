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
use Psr\Log\NullLogger;

class SimplePoolTest extends TestCase
{
    private $connections;

    protected function setUp(): void
    {
        $this->connections = [];
    }

    public function testReturnRefererence()
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
            new NullLogger()
        );
        $connection = $pool->take();
        foreach ($this->connections as $i => $c) {
            $this->connections[$i] = null;
        }
        $connection = $pool->take();
        $events = $eventDispatcher->getEvents();
        $this->assertCount(2, $events);
    }

    public function testReturnConnection()
    {
        $eventDispatcher = new InMemoryEventDispatcher();
        $pool = new SimplePool(
            'test',
            function ($connectionId) {
                error_log("create connection $connectionId");

                return time();
            },
            new PoolConfig(),
            $eventDispatcher,
            new NullLogger()
        );
        $connection = $pool->take();
        foreach ($this->connections as $i => $c) {
            $this->connections[$i] = null;
        }
        $connection = $pool->take();
        $events = $eventDispatcher->getEvents();
        $this->assertCount(1, $events);
    }
}
