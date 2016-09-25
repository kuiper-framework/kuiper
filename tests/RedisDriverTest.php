<?php
namespace kuiper\cache;

use kuiper\cache\Pool;
use kuiper\cache\Item;
use kuiper\cache\driver\Redis;

class RedisDriverTest extends BaseDriverTestCase
{
    public function setUp()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped("extension redis is required");
        }
    }

    protected function createCachePool()
    {
        $driver = new Redis([
            'servers' => [['host' => getenv('REDIS_PORT_6379_TCP_ADDR')]]
        ]);
        $driver->getConnection()->flushdb();
        return new Pool($driver);
    }
}