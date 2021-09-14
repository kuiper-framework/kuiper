<?php

declare(strict_types=1);

namespace kuiper\cache;

use DI\Annotation\Inject;
use kuiper\di\annotation\AllConditions;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnClass;
use kuiper\di\annotation\ConditionalOnProperty;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\pool\ConnectionProxyGenerator;
use kuiper\swoole\pool\PoolFactoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Stash\Driver\Composite;
use Stash\Driver\Ephemeral;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class CacheConfiguration
{
    /**
     * @Bean
     * @Inject({"redisConfig": "application.redis"})
     */
    public function redis(PoolFactoryInterface $poolFactory, ?array $redisConfig): \Redis
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ConnectionProxyGenerator::create($poolFactory, \Redis::class, static function () use ($redisConfig) {
            return RedisFactory::createConnection(RedisFactory::buildDsn($redisConfig), $redisConfig);
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
            (int) ($config['max_lifetime'] ?? 2 * $defaultLifetime),
            (int) ($config['max_items'] ?? 618)
        );
    }

    /**
     * @Bean()
     * @Inject({"cacheConfig": "application.cache"})
     * @AllConditions(
     *     @ConditionalOnClass(ChainAdapter::class),
     *     @ConditionalOnProperty("application.cache.implementation", hasValue="symfony", matchIfMissing=true)
     * )
     */
    public function symfonyCacheItemPool(LoggerFactoryInterface $loggerFactory, ArrayAdapter $arrayAdapter, \Redis $redis, ?array $cacheConfig): CacheItemPoolInterface
    {
        $namespace = $cacheConfig['namespace'] ?? '';
        $defaultLifeTime = (int) ($cacheConfig['lifetime'] ?? 0);

        $redisAdapter = new RedisAdapter($redis, $namespace, $defaultLifeTime);
        $redisAdapter->setLogger($loggerFactory->create(__CLASS__));
        if (isset($cacheConfig['memory'])) {
            return new ChainAdapter([$arrayAdapter, $redisAdapter]);
        }

        return $redisAdapter;
    }

    /**
     * @Bean
     * @Inject({"config": "application.cache.memory"})
     */
    public function stashEphemeral(?array $config): Ephemeral
    {
        return new Ephemeral(['maxItems' => $config['max_items'] ?? 618]);
    }

    /**
     * @Bean()
     * @Inject({"cacheConfig": "application.cache"})
     * @AllConditions(
     *     @ConditionalOnClass(\Stash\Pool::class),
     *     @ConditionalOnProperty("application.cache.implementation", hasValue="stash", matchIfMissing=true)
     * )
     */
    public function stashCacheItemPool(\Redis $redis, Ephemeral $ephemeral, ?array $cacheConfig): CacheItemPoolInterface
    {
        $driver = new RedisDriver([
            'redis' => $redis,
            'prefix' => $cacheConfig['namespace'] ?? '',
        ]);
        if (isset($cacheConfig['memory'])) {
            $driver = new Composite(['drivers' => [$ephemeral, $driver]]);
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
        return new SimpleCache($cacheItemPool);
    }
}
