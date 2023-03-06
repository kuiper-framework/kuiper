<?php

declare(strict_types=1);

namespace kuiper\cache;

use Dotenv\Dotenv;
use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use kuiper\logger\LoggerConfiguration;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use PHPUnit\Framework\TestCase;
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

    protected function createContainer(array $config = []): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $properties = Properties::create($config);
        $builder->addConfiguration(new LoggerConfiguration());
        $builder->addConfiguration(new CacheConfiguration());
        $builder->addDefinitions(new PropertiesDefinitionSource($properties));
        $builder->addDefinitions([
            PropertyResolverInterface::class => $properties,
            PoolFactoryInterface::class => new PoolFactory(),
        ]);

        return $builder->build();
    }
}
