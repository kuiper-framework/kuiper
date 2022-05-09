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

namespace kuiper\rpc\registry;

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\cache\CacheConfiguration;
use kuiper\di\AwareInjection;
use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\helper\PropertyResolverInterface;
use kuiper\http\client\HttpClientConfiguration;
use kuiper\logger\LoggerConfiguration;
use kuiper\logger\LoggerFactory;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\reflection\ReflectionConfiguration;
use kuiper\rpc\servicediscovery\InMemoryServiceResolver;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\serializer\SerializerConfiguration;
use kuiper\swoole\Application;
use kuiper\swoole\config\DiactorosHttpMessageFactoryConfiguration;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected  ?ContainerInterface $container = null;

    protected function createContainer(array $configArr): ContainerInterface
    {
        $_SERVER['APP_PATH'] = dirname(__DIR__, 2);
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new ReflectionConfiguration());
        $builder->addConfiguration(new CacheConfiguration());
        $builder->addConfiguration(new SerializerConfiguration());
        $builder->addConfiguration(new LoggerConfiguration());
        $builder->addConfiguration(new HttpClientConfiguration());
        $builder->addConfiguration(new RpcRegistryConfiguration());
        $builder->addConfiguration(new DiactorosHttpMessageFactoryConfiguration());
        $app = Application::create(function () use ($builder) {
            return $builder->build();
        });
        $config = Application::getInstance()->getConfig();
        $config->merge($configArr);
        $builder->addDefinitions(new PropertiesDefinitionSource($config));
        $builder->addDefinitions([
            PoolFactoryInterface::class => new PoolFactory(false),
            PropertyResolverInterface::class => $config,
            ServiceResolverInterface::class => new InMemoryServiceResolver(),
        ]);
        $builder->defer(function (ContainerInterface $container) {
            foreach ($this->getDefinitions() as $name => $definition) {
                $container->set($name, $definition);
            }
        });

        return $app->getContainer();
    }

    protected function getDefinitions(): array
    {
        return [];
    }

    protected function getConfig(): array
    {
        return [];
    }

    protected function getContainer(): ContainerInterface
    {
        if (!$this->container) {
            throw new \BadMethodCallException('call createContainer first');
        }

        return $this->container;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createContainer($this->getConfig());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }
}
