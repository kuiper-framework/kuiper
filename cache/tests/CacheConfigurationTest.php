<?php

declare(strict_types=1);

namespace kuiper\cache;

use function DI\factory;
use Dotenv\Dotenv;
use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use function kuiper\helper\env;
use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use kuiper\logger\LoggerFactory;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Stash\Driver\Ephemeral;
use Stash\Pool;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class CacheConfigurationTest extends TestCase
{
    /**
     * {@inheritDoc}
     */
    public static function setUpBeforeClass(): void
    {
        Dotenv::createImmutable(__DIR__)->safeLoad();
    }

    public function testCheckConfigCondition()
    {
        $container = $this->createContainer([]);
        $this->assertFalse($container->has(CacheItemPoolInterface::class));
        $container = $this->createContainer([
            'application' => [
                'cache' => [
                    'memory' => [],
                ],
            ],
        ]);
        $this->assertTrue($container->has(CacheItemPoolInterface::class));
    }

    public function testRedis()
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
        $this->assertTrue((bool) $redis->exists('foo'));
    }

    public function testDefaultCacheUsingStash()
    {
        $container = $this->createContainer([
            'application' => [
                'cache' => [
                ],
            ],
        ]);
        $cache = $container->get(CacheItemPoolInterface::class);
        $this->assertInstanceOf(Pool::class, $cache);
    }

    public function testCacheUsingSymfony()
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

    public function testGroupDemo()
    {
        $cache = $this->createCache();
        $item = $cache->getItem('group.foo/bar');
        $cache->save($item->set('bar content'));
        $cache->deleteItem('group.foo');
        $this->assertFalse($cache->hasItem('group.foo/bar'));
    }

    public function testSimpleCache()
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

    public function testMemoryCache()
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
        // $cache->set('foo', 'bar');
        $memory = $container->get(Ephemeral::class);
        $memory->storeData(['foo'], ['return' => 'bar'], time() + 20);
        $ret = $cache->get('foo');
        $this->assertEquals('bar', $ret);
        //var_export([$ret]);
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
        $container = $this->createContainer([
            'application' => [
                'cache' => [
                    'namespace' => 'test.',
                    'lifetime' => 1000,
                    'memory' => [],
                ],
            ],
        ]);

        return $container->get(CacheItemPoolInterface::class);
    }

    /**
     * @return ContainerInterface
     */
    protected function createContainer(array $config): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $config = Properties::create($config);
        $builder->addConfiguration(new CacheConfiguration());
        $builder->addDefinitions(new PropertiesDefinitionSource($config));
        $builder->addDefinitions([
            PropertyResolverInterface::class => $config,
            PoolFactoryInterface::class => new PoolFactory(),
            LoggerFactoryInterface::class => factory(function (ContainerInterface $container) {
                return new LoggerFactory($container, [
                    'loggers' => [
                        'root' => ['console' => true],
                    ],
                ]);
            }),
        ]);

        return $builder->build();
    }
}
