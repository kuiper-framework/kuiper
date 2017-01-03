<?php

namespace kuiper\cache;

use kuiper\cache\driver\None;

class NoneDriverTest extends TestCase
{
    protected function createCachePool()
    {
        return new Pool(new None());
    }

    public function testSet()
    {
        $pool = $this->createCachePool();
        $item = $pool->getItem('foo');
        $this->assertTrue($item instanceof Item);
        $this->assertFalse($item->isHit());
    }
}
