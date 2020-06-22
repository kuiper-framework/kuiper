<?php

declare(strict_types=1);

namespace kuiper\cache;

use DI\Annotation\Inject;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\Configuration;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\swoole\pool\PoolInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * @Configuration()
 */
class CacheConfiguration
{
    /**
     * @Bean("redisPool")
     * @Inject({"redisConfig": "application.redis"})
     */
    public function redisPool(PoolFactoryInterface $poolFactory, ?array $redisConfig): PoolInterface
    {
        $redisConfig = ($redisConfig ?? []);
        $dsn = sprintf('redis://%s%s:%d',
            isset($redisConfig['password']) ? $redisConfig['password'].'@' : '',
            $redisConfig['host'] ?? 'localhost',
            $redisConfig['port'] ?? 6379);

        return $poolFactory->create('redis', static function () use ($dsn, $redisConfig) {
            return RedisAdapter::createConnection($dsn, $redisConfig);
        });
    }

    /**
     * @Bean()
     * @Inject({"redisPool": "redisPool", "cacheConfig": "application.cache"})
     */
    public function cacheItemPool($redisPool, ?array $cacheConfig): CacheItemPoolInterface
    {
        $namespace = $cacheConfig['namespace'] ?? '';
        $defaultLifeTime = $cacheConfig['lifetime'] ?? 0;

        return new RedisPoolAdapter($redisPool, $namespace, $defaultLifeTime);
    }
}
