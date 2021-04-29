<?php

declare(strict_types=1);

namespace kuiper\cache;

use DI\Annotation\Inject;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnClass;
use kuiper\di\annotation\ConditionalOnProperty;
use kuiper\di\annotation\Configuration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\swoole\pool\PoolInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Stash\Driver\Composite;
use Stash\Driver\Ephemeral;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
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
        $database = (int) ($redisConfig['database'] ?? 0);
        if (0 !== $database) {
            $dsn .= '/'.$database;
        }

        return $poolFactory->create('redis', static function () use ($dsn, $redisConfig) {
            return RedisFactory::createConnection($dsn, $redisConfig);
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
            (int) ($config['max-items'] ?? 618)
        );
    }

    /**
     * @Bean()
     * @Inject({"redisPool": "redisPool", "cacheConfig": "application.cache"})
     * @ConditionalOnClass(ChainAdapter::class)
     */
    public function symfonyCacheItemPool(LoggerFactoryInterface $loggerFactory, PoolInterface $redisPool, ?array $cacheConfig): CacheItemPoolInterface
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
     * @Inject({"redisPool": "redisPool", "cacheConfig": "application.cache"})
     * @ConditionalOnClass(\Stash\Pool::class)
     */
    public function stashCacheItemPool(PoolInterface $redisPool, ?array $cacheConfig): CacheItemPoolInterface
    {
        $driver = new RedisDriver($redisPool, [
            'redisPool' => $redisPool,
            'prefix' => $cacheConfig['namespace'] ?? '',
        ]);
        if (isset($cacheConfig['memory'])) {
            $driver = new Composite([
                'drivers' => [
                    new Ephemeral(['maxItems' => $cacheConfig['memory']['max-items'] ?? 618]),
                    $driver,
                ],
            ]);
        }

        $pool = new CacheItemPool($driver);
        $lifetime = (int) ($cacheConfig['lifetime'] ?? 0);
        if ($lifetime > 0) {
            $pool->setDefaultTtl($lifetime);
        }

        return $pool;
    }

    /**
     * @Bean()
     */
    public function simpleCache(CacheItemPoolInterface $cacheItemPool): CacheInterface
    {
        return new Psr16Cache($cacheItemPool);
    }
}
