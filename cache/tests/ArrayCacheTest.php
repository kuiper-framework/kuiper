<?php

namespace kuiper\cache;

use PHPUnit\Framework\TestCase;

class ArrayCacheTest extends TestCase
{
    private int $time = 0;

    private function timeFactory(): callable
    {
        return function () {
            return $this->time;
        };
    }

    public function testSet()
    {
        $cache = new ArrayCache();
        $cache->set("foo", "fooValue");
        $this->assertEquals($cache->get("foo"), "fooValue");
    }

    public function testExpire()
    {
        $cache = new ArrayCache(capacity: 4, timeFactory: $this->timeFactory());

        $cache->set("foo", "fooValue");
        $this->time = 61;
        $this->assertNull($cache->get("foo"));
    }

    public function testSetFlush()
    {
        $capacity = 4;
        $cache = new ArrayCache(capacity: $capacity, timeFactory: $this->timeFactory());
        $keys = [];
        foreach (range(1, 10) as $key) {
            $this->time += 20;
            $cacheKey = "foo{$key}";
            $keys[] = $cacheKey;
            $cache->set($cacheKey, "value{$key}");
            $values = $cache->getMultiple($keys);
            $this->assertLessThan($capacity, count(array_filter($values)));
            // error_log("$key: " . var_export($values, true));
        }
    }
}
