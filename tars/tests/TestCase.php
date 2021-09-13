<?php

declare(strict_types=1);

namespace kuiper\tars;

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\AwareInjection;
use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\helper\PropertyResolverInterface;
use kuiper\http\client\HttpClientConfiguration;
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
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected function createContainer(array $configArr): ContainerInterface
    {
        $_SERVER['APP_PATH'] = dirname(__DIR__, 2);
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new SerializerConfiguration());
        $builder->addConfiguration(new HttpClientConfiguration());
        $builder->addConfiguration(new DiactorosHttpMessageFactoryConfiguration());
        $app = Application::create(function () use ($builder) {
            return $builder->build();
        });
        $config = Application::getInstance()->getConfig();
        $config->merge($configArr);
        $builder->addDefinitions(new PropertiesDefinitionSource($config));
        $builder->addAwareInjection(AwareInjection::create(LoggerAwareInterface::class));
        $builder->addDefinitions([
            LoggerInterface::class => new Logger('test', [new ErrorLogHandler()]),
            PoolFactoryInterface::class => new PoolFactory(false),
            PropertyResolverInterface::class => $config,
            ServiceResolverInterface::class => new InMemoryServiceResolver(),
            AnnotationReaderInterface::class => AnnotationReader::getInstance(),
        ]);

        return $app->getContainer();
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
