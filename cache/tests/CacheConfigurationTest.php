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

namespace kuiper\cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Redis;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class CacheConfigurationTest extends CacheTestCase
{
    public function testCheckConfigCondition(): void
    {
        $this->assertTrue($this->createContainer()->has(CacheItemPoolInterface::class));
    }

    public function testRedis(): void
    {
        $redis = $this->createContainer()->get(Redis::class);
        $this->assertInstanceOf(Redis::class, $redis);
        $ret = $redis->set('foo', 'bar');
        $this->assertTrue($ret);
        $this->assertTrue((bool) $redis->exists('foo'));
    }

    public function testCachePsr6(): void
    {
        $container = $this->createContainer([
            'application' => [
                'cache' => [
                    'namespace' => 'test',
                ],
            ],
        ]);
        $cache = $container->get(CacheItemPoolInterface::class);
        $item = $cache->getItem('foo');
        $item->set((object) ['a' => 1]);
        $cache->save($item);
        $this->assertTrue((bool) $container->get(Redis::class)->exists('test:foo'));
    }

    public function testCacheUsingSymfony(): void
    {
        $cache = $this->createContainer()->get(CacheItemPoolInterface::class);
        $this->assertInstanceOf(AdapterInterface::class, $cache);
    }

    public function testSimpleCache(): void
    {
        $cache = $this->createContainer()->get(CacheInterface::class);
        $key = 'foo';
        $cache->delete($key);
        $value = $cache->get($key);

        $cache->set($key, date('c'));
        $newValue = $cache->get($key);
        // var_export([$value, $newValue]);
        $this->assertNull($value);
        $this->assertNotNull($newValue);
        $this->assertInstanceOf(SimpleCache::class, $cache);
    }
}
