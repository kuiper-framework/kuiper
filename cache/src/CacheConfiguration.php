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
use kuiper\swoole\attribute\BootstrapConfiguration;
use function DI\factory;
use kuiper\di\attribute\AllConditions;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\ConditionalOnClass;
use kuiper\di\attribute\ConditionalOnProperty;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\pool\ConnectionProxyGenerator;
use kuiper\swoole\pool\PoolFactoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
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
        return [
            \Symfony\Contracts\Cache\CacheInterface::class => factory([$this, 'symfonyCacheItemPool']),
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
    public function redis(PoolFactoryInterface $poolFactory, #[Inject('application.redis')] ?array $redisConfig): \Redis
    {
        if (!isset($redisConfig)) {
            $redisConfig = [];
        }

        return ConnectionProxyGenerator::create($poolFactory, \Redis::class, static function () use ($redisConfig) {
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

        $redisAdapter = new RedisTagAwareAdapter($container->get(\Redis::class), $namespace, $defaultLifeTime);
        $redisAdapter->setLogger($container->get(LoggerFactoryInterface::class)->create(__CLASS__));

        return $redisAdapter;
    }

    #[Bean]
    #[AllConditions(
        new ConditionalOnClass(ChainAdapter::class),
        new ConditionalOnProperty('application.cache.implementation', 'symfony', true)
    )]
    public function symfonyCacheItemPool(ContainerInterface $container): CacheItemPoolInterface
    {
        return new ChainAdapter([
            $container->get(ArrayAdapter::class),
            $container->get('symfonyRedisCache'),
        ]);
    }

    #[Bean]
    public function simpleCache(CacheItemPoolInterface $cacheItemPool): CacheInterface
    {
        return new SimpleCache($cacheItemPool);
    }
}
