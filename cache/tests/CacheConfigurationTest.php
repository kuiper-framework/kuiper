<?php

declare(strict_types=1);

namespace kuiper\cache;

use kuiper\swoole\pool\PoolFactory;
use PHPUnit\Framework\TestCase;

class CacheConfigurationTest extends TestCase
{
    public function testRedisPool()
    {
        $config = new CacheConfiguration();
        $poolFactory = new PoolFactory();
        $redis = $config->redisPool($poolFactory, [
            'host' => '127.0.0.1',
            'database' => 2,
        ])->take();
        $ret = $redis->set('foo', 'bar');
        $this->assertTrue($ret);
    }
}
