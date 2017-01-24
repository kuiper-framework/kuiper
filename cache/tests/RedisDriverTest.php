<?php

namespace kuiper\cache;

use kuiper\cache\driver\Redis;

class RedisDriverTest extends BaseDriverTestCase
{
    public function setUp()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('extension redis is required');
        }
    }

    protected function createCachePool()
    {
        $driver = new Redis([
            'servers' => [['host' => getenv('REDIS_PORT_6379_TCP_ADDR') ?: 'localhost']],
            'database' => 15,
        ]);
        $driver->getRedis()->flushdb();

        return new Pool($driver, ['prefix' => 'redis_', 'lifetime' => 300]);
    }
}
