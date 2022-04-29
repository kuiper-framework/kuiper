<?php

namespace kuiper\swoole\coroutine;

use PHPUnit\Framework\TestCase;

class ChannelTest extends TestCase
{
    public function testName()
    {
        $channel = new Channel(10);
        $channel->push(1);
        $this->assertEquals(1, $channel->size());
        $data = $channel->pop();
        $this->assertEquals(1, $data);
        $this->assertEquals(0, $channel->size());
    }

}
