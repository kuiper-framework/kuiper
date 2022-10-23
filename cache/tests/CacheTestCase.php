<?php

declare(strict_types=1);

namespace kuiper\cache;

use function DI\factory;
use Dotenv\Dotenv;
use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use kuiper\logger\LoggerFactory;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;

abstract class CacheTestCase extends TestCase
{
    /**
     * {@inheritDoc}
     */
    public static function setUpBeforeClass(): void
    {
        Dotenv::createImmutable(__DIR__)->safeLoad();
    }

    protected function createCache(): CacheItemPoolInterface
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

    protected function createContainer(array $config): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $properties = Properties::create($config);
        $builder->addConfiguration(new CacheConfiguration());
        $builder->addDefinitions(new PropertiesDefinitionSource($properties));
        $builder->addDefinitions([
            PropertyResolverInterface::class => $properties,
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
