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
use Symfony\Component\Cache\Adapter\AdapterInterface;
use function kuiper\helper\env;

class CacheConfigurationTest extends CacheTestCase
{
    public function testCheckConfigCondition(): void
    {
        $container = $this->createContainer([
            'application' => [
                'cache' => [
                    'memory' => [],
                ],
            ],
        ]);
        $this->assertTrue($container->has(CacheItemPoolInterface::class));
    }

    public function testRedis(): void
    {
        $redis = $this->createContainer([
            'application' => [
                'redis' => [
                    'host' => env('REDIS_HOST'),
                ],
            ],
        ])->get(\Redis::class);
        $this->assertInstanceOf(\Redis::class, $redis);
        $ret = $redis->set('foo', 'bar');
        $this->assertTrue($ret);
        $this->assertTrue((bool)$redis->exists('foo'));
    }

    public function testCacheUsingSymfony(): void
    {
        $container = $this->createContainer([
            'application' => [
                'cache' => [
                    'implementation' => 'symfony',
                ],
            ],
        ]);
        $cache = $container->get(CacheItemPoolInterface::class);
        $this->assertInstanceOf(AdapterInterface::class, $cache);
    }

    public function testSimpleCache(): void
    {
        $cache = $this->createContainer([
            'application' => [
                'cache' => [],
            ],
        ])->get(CacheInterface::class);
        $key = 'foo';
        $cache->delete($key);
        $value = $cache->get($key);

        $cache->set($key, date('c'));
        $newValue = $cache->get($key);
        // var_export([$value, $newValue]);
        $this->assertNull($value);
        $this->assertNotNull($newValue);
    }

    public function testMemoryCache(): void
    {
        $container = $this->createContainer([
            'application' => [
                'cache' => [
                    'memory' => [],
                ],
            ],
        ]);
        $cache = $container->get(CacheInterface::class);
        $cache->delete('foo');
        $this->assertInstanceOf(SimpleCache::class, $cache);
    }

}
