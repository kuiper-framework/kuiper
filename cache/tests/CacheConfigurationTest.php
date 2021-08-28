<?php

declare(strict_types=1);

namespace kuiper\cache;

use Dotenv\Dotenv;
use function kuiper\helper\env;
use kuiper\swoole\pool\PoolFactory;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

class CacheConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        Dotenv::createImmutable(__DIR__)->safeLoad();
    }

    private function createRedisPool()
    {
        $config = new CacheConfiguration();
        $poolFactory = new PoolFactory();

        return $config->redisPool($poolFactory, [
            'host' => env('REDIS_HOST'),
            'database' => 2,
        ]);
    }

    public function testRedisPool()
    {
        $redis = $this->createRedisPool()->take();
        $ret = $redis->set('foo', 'bar');
        $this->assertTrue($ret);
    }

    public function testCachePool()
    {
        $cache = $this->createCache();
        $key = 'foo';
        $data = 'bar';

        $cache->deleteItem($key);
        $fetch = $this->createFetch($cache);
        $this->assertEquals($data, $fetch($key, $data));
        $this->assertEquals($data, $fetch($key));
        $cache->deleteItem($key);
        $this->assertEquals(null, $fetch($key));
    }

    public function testCacheGroup()
    {
        $cache = $this->createCache();
        $parentKey = 'Group.foo';
        $childKey = 'Group.foo/bar';

        $cache->deleteItem($parentKey);
        $fetch = $this->createFetch($cache);
        $this->assertEquals('foo', $fetch($parentKey, 'foo'));
        $this->assertEquals('bar', $fetch($childKey, 'bar'));

        $this->assertEquals('foo', $fetch($parentKey));
        $this->assertEquals('bar', $fetch($childKey));

        $cache->deleteItem($parentKey);
        $this->assertEquals(null, $fetch($parentKey));
        $this->assertEquals(null, $fetch($childKey));
    }

    public function testSimpleCache()
    {
        $config = new CacheConfiguration();
        $cache = $config->simpleCache($this->createCache());
        $key = 'foo';
        $cache->delete($key);
        $value = $cache->get($key);

        $cache->set($key, date('c'));
        $newValue = $cache->get($key);
        // var_export([$value, $newValue]);
        $this->assertNull($value);
        $this->assertNotNull($newValue);
    }

    private function createFetch($cache): \Closure
    {
        return static function ($key, $data = null) use ($cache) {
            $item = $cache->getItem($key);
            if (isset($data) && !$item->isHit()) {
                $item->set($data);
                $cache->save($item);
            }

            return $item->get();
        };
    }

    private function createCache(): CacheItemPoolInterface
    {
        $config = new CacheConfiguration();

        return $config->stashCacheItemPool($this->createRedisPool(), [
            'namespace' => 'test.',
            'lifetime' => 1000,
        ]);
    }
}
