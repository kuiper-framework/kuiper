<?php

declare(strict_types=1);

namespace kuiper\cache;

use DI\Annotation\Inject;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnProperty;
use kuiper\di\annotation\Configuration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\swoole\pool\PoolInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * @Configuration()
 * @ConditionalOnProperty("application.redis")
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
     * @Inject({"config": "application.cache.memory"})
     */
    public function arrayCache(?array $config): ArrayAdapter
    {
        $defaultLifetime = $config['lifetime'] ?? 2;

        return new ArrayAdapter(
            $defaultLifetime,
            $config['serialize'] ?? true,
            (int) ($config['max-lifetime'] ?? 2 * $defaultLifetime),
            (int) ($config['max-items'] ?? 0)
        );
    }

    /**
     * @Bean()
     * @Inject({"redisPool": "redisPool", "cacheConfig": "application.cache"})
     */
    public function cacheItemPool(LoggerFactoryInterface $loggerFactory, PoolInterface $redisPool, ?array $cacheConfig): CacheItemPoolInterface
    {
        $namespace = $cacheConfig['namespace'] ?? '';
        $defaultLifeTime = (int) ($cacheConfig['lifetime'] ?? 0);

        $redisAdapter = new RedisPoolAdapter($redisPool, $namespace, $defaultLifeTime);
        $redisAdapter->setLogger($loggerFactory->create(RedisPoolAdapter::class));
        if (isset($cacheConfig['memory'])) {
            return new ChainAdapter([
                $this->arrayCache($cacheConfig['memory']),
                $redisAdapter,
            ]);
        }

        return $redisAdapter;
    }

    /**
     * @Bean()
     */
    public function simpleCache(CacheItemPoolInterface $cacheItemPool): CacheInterface
    {
        return new Psr16Cache($cacheItemPool);
    }
}
