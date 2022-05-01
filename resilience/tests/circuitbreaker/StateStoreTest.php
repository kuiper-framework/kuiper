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

namespace kuiper\resilience\circuitbreaker;

use PHPUnit\Framework\TestCase;

class StateStoreTest extends TestCase
{
    /**
     * @dataProvider stores
     */
    public function testSetState(StateStore $store): void
    {
        $name = 'foo.h.h::foo';
        $store->clear($name);
        $state = $store->getState($name);
        $this->assertEquals(State::CLOSED, $state);
        $this->assertEquals(0, $store->getOpenAt($name));
        $store->setState($name, State::OPEN);
        $this->assertEquals(State::OPEN, $store->getState($name));
        $this->assertTrue($store->getOpenAt($name) > 0);
    }

    public function stores(): array
    {
        $redis = new \Redis();
        $redis->connect('localhost');
        $store = new RedisStateStore($redis);

        return [
            [$store],
            [new SwooleTableStateStore()],
        ];
    }
}
