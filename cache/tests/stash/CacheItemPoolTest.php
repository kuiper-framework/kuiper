<?php

declare(strict_types=1);

namespace kuiper\cache\stash;

use kuiper\cache\stash\driver\RedisDriver;
use PHPUnit\Framework\TestCase;
use Redis;

class CacheItemPoolTest extends TestCase
{
    public function testName()
    {
        $cache = $this->getCache();
        $item = $cache->getItem('foo');
        if (!$item->isHit()) {
            $item->set('bar');
            $cache->save($item);
        }
        $this->assertEquals('bar', $item->get());
    }

    public function testClearGroup()
    {
        $cache = $this->getCache();
        $item = $cache->getItem('group.foo/bar');
        if (!$item->isHit()) {
            $item->set('bar');
            $cache->save($item);
        }
        $this->assertEquals('bar', $item->get());
        $cache->deleteItem('group.foo/');
        $this->assertFalse($cache->hasItem('group.foo/bar'));
    }

    private function getCache(): CacheItemPool
    {
        $redis = new Redis();
        $redis->connect('localhost');
        error_log(spl_object_id($redis));
        $cache = new CacheItemPool(new RedisDriver([
            'redis' => $redis,
            'prefix' => 'test:',
        ]));

        return $cache;
    }
}
