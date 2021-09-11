<?php

declare(strict_types=1);

namespace kuiper\resilience\circuitbreaker;

use PHPUnit\Framework\TestCase;

class StateStoreTest extends TestCase
{
    /**
     * @dataProvider stores
     */
    public function testName(StateStore $store)
    {
        $name = 'foo.h.h::foo';
        $store->clear($name);
        $state = $store->getState($name);
        $this->assertEquals(State::CLOSED, $state->value);
        $this->assertEquals(0, $store->getOpenAt($name));
        $store->setState($name, State::OPEN());
        $this->assertEquals(State::OPEN, $store->getState($name)->value);
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
