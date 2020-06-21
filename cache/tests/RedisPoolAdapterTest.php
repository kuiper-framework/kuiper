<?php

declare(strict_types=1);

namespace kuiper\cache;

use kuiper\swoole\monolog\CoroutineIdProcessor;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\SingleConnectionPool;
use Monolog\Handler\ErrorLogHandler;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Swoole\Coroutine;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class RedisPoolAdapterTest extends TestCase
{
    public function testGet()
    {
        $redisPool = new SingleConnectionPool('redis', function () {
            return RedisAdapter::createConnection(sprintf('redis://%s?dbindex=%d',
                getenv('REDIS_HOST') ?: 'localhost',
                getenv('REDIS_DATABASE') ?: 1));
        });
        /** @var CacheItemPoolInterface $cache */
        $cache = new RedisPoolAdapter($redisPool, 'test', 300);
        $item = $cache->getItem('foo');
        if (!$item->isHit()) {
            $item->set('foo-value');
            $cache->save($item);
        }
        $this->assertEquals('foo-value', $item->get());
    }

    public function testCoroutine()
    {
        $logger = new \Monolog\Logger('',
            [new ErrorLogHandler()],
            [new CoroutineIdProcessor()]);
        $poolFactory = new PoolFactory([], $logger);
        Coroutine::set(['hook_flags' => SWOOLE_HOOK_ALL]);
        $tasks = array_fill(0, 3, function () use ($poolFactory) {
            $redisPool = $poolFactory->create('redis', function () {
                return RedisAdapter::createConnection(sprintf('redis://%s?dbindex=%d',
                    getenv('REDIS_HOST') ?: 'localhost',
                    getenv('REDIS_DATABASE') ?: 1));
            });
            /** @var CacheItemPoolInterface $cache */
            $cache = new RedisPoolAdapter($redisPool, 'test', 300);
            $item = $cache->getItem('foo');
            if (!$item->isHit()) {
                $item->set('foo-value');
                $cache->save($item);
            }
            $this->assertEquals('foo-value', $item->get());
        });
        array_walk($tasks, [Coroutine::class, 'create']);
    }
}
