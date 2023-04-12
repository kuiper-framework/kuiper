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

use DI\Attribute\Inject;

use function DI\get;

use kuiper\cache\stash\CacheItemPool;
use kuiper\cache\stash\driver\Composite;
use kuiper\cache\stash\driver\Ephemeral;
use kuiper\cache\stash\driver\RedisDriver;
use kuiper\di\attribute\AllConditions;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\ConditionalOnClass;
use kuiper\di\attribute\ConditionalOnProperty;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;

use function kuiper\helper\env;

use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\Application;
use kuiper\swoole\attribute\BootstrapConfiguration;
use kuiper\swoole\pool\ConnectionProxyGenerator;
use kuiper\swoole\pool\PoolFactoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Redis;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;

#[BootstrapConfiguration]
class CacheConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        if (class_exists(Application::class) && Application::hasInstance()) {
            Application::getInstance()->getConfig()->mergeIfNotExists([
                'application' => [
                    'cache' => [
                        'implementation' => env('CACHE_IMPLEMENTATION'),
                        'namespace' => env('CACHE_NAMESPACE'),
                        'lifetime' => (int) env('CACHE_LIFETIME', '0'),
                        'memory' => [
                            'lifetime' => (int) env('CACHE_MEMORY_LIFETIME', '5'),
                            'max_items' => (int) env('CACHE_MEMORY_MAX_ITEMS', '1000'),
                            'serialize' => 'true' === env('CACHE_MEMORY_SERIALIZE'),
                        ],
                    ],
                    'redis' => [
                        'host' => env('REDIS_HOST', 'localhost'),
                        'port' => (int) env('REDIS_PORT', '6379'),
                        'password' => env('REDIS_PASSWORD', env('REDIS_PASS')),
                        'database' => (int) env('REDIS_DATABASE', '0'),
                    ],
                ],
            ]);
        }

        return [
            \Symfony\Contracts\Cache\CacheInterface::class => get('symfonyCache'),
        ];
    }

    public static function buildDsn(array $options): string
    {
        $dsn = sprintf('redis://%s%s:%d',
            isset($options['password']) ? $options['password'].'@' : '',
            $options['host'] ?? 'localhost',
            $options['port'] ?? 6379);
        $database = (int) ($options['database'] ?? 0);
        if (0 !== $database) {
            $dsn .= '/'.$database;
        }

        return $dsn;
    }

    #[Bean]
    public function redis(PoolFactoryInterface $poolFactory, #[Inject('application.redis')] ?array $redisConfig): Redis
    {
        if (!isset($redisConfig)) {
            $redisConfig = [];
        }

        return ConnectionProxyGenerator::create($poolFactory, Redis::class, static function () use ($redisConfig) {
            return RedisAdapter::createConnection(self::buildDsn($redisConfig), $redisConfig);
        });
    }

    #[Bean]
    public function arrayCache(#[Inject('application.cache.memory')] ?array $config): ArrayAdapter
    {
        $defaultLifetime = $config['lifetime'] ?? 2;

        return new ArrayAdapter(
            $defaultLifetime,
            $config['serialize'] ?? true,
            (int) ($config['max_lifetime'] ?? 2 * $defaultLifetime),
            (int) ($config['max_items'] ?? 618)
        );
    }

    #[Bean('symfonyRedisCache')]
    public function symfonyRedisCache(ContainerInterface $container): \Symfony\Contracts\Cache\CacheInterface
    {
        $config = $container->get('application.cache');
        $namespace = $config['namespace'] ?? '';
        $defaultLifeTime = (int) ($config['lifetime'] ?? 0);

        $redisAdapter = new RedisAdapter($container->get(Redis::class), $namespace, $defaultLifeTime);
        $redisAdapter->setLogger($container->get(LoggerFactoryInterface::class)->create(RedisAdapter::class));

        return $redisAdapter;
    }

    #[Bean('symfonyCache')]
    public function symfonyCache(ContainerInterface $container): \Symfony\Contracts\Cache\CacheInterface
    {
        $config = $container->get('application.cache');
        $namespace = $config['namespace'] ?? '';
        $defaultLifeTime = (int) ($config['lifetime'] ?? 0);

        $redisAdapter = new RedisTagAwareAdapter($container->get(Redis::class), $namespace, $defaultLifeTime);
        $redisAdapter->setLogger($container->get(LoggerFactoryInterface::class)->create(RedisTagAwareAdapter::class));

        return $redisAdapter;
    }

    #[Bean]
    #[AllConditions(
        new ConditionalOnClass(ChainAdapter::class),
        new ConditionalOnProperty('application.cache.implementation', 'symfony')
    )]
    public function symfonyCacheItemPool(ContainerInterface $container): CacheItemPoolInterface
    {
        return new ChainAdapter([
            $container->get(ArrayAdapter::class),
            $container->get('symfonyRedisCache'),
        ]);
    }

    #[Bean]
    #[ConditionalOnProperty('application.cache.implementation', 'kuiper', matchIfMissing: true)]
    public function kuiperCacheItemPool(ContainerInterface $container): CacheItemPoolInterface
    {
        $options = $container->get('application.cache');
        $cacheItemPool = new CacheItemPool(new Composite([
            new Ephemeral($options['memory'] ?? []),
            new RedisDriver(array_merge([
                'redis' => $container->get(Redis::class),
                'prefix' => $options['namespace'] ?? '',
            ])),
        ]));
        $lifetime = (int) ($cacheConfig['lifetime'] ?? 0);
        if ($lifetime > 0) {
            $cacheItemPool->setDefaultTtl($lifetime);
        }

        return $cacheItemPool;
    }

    #[Bean]
    public function simpleCache(CacheItemPoolInterface $cacheItemPool): CacheInterface
    {
        return new SimpleCache($cacheItemPool);
    }
}
