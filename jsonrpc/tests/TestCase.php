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

namespace kuiper\jsonrpc;

use function DI\factory;
use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\ContainerBuilder;
use kuiper\event\EventConfiguration;
use kuiper\helper\PropertyResolverInterface;
use kuiper\http\client\HttpClientConfiguration;
use kuiper\logger\LoggerConfiguration;
use kuiper\logger\LoggerFactory;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\reflection\ReflectionConfiguration;
use kuiper\reflection\ReflectionNamespaceFactory;
use kuiper\resilience\ResilienceConfiguration;
use kuiper\rpc\servicediscovery\InMemoryServiceResolver;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\serializer\SerializerConfiguration;
use kuiper\swoole\Application;
use kuiper\swoole\config\DiactorosHttpMessageFactoryConfiguration;
use kuiper\swoole\config\FoundationConfiguration;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected function createContainer(): ContainerInterface
    {
        $_SERVER['APP_PATH'] = dirname(__DIR__, 2);
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new EventConfiguration());
        $builder->addConfiguration(new ReflectionConfiguration());
        $builder->addConfiguration(new LoggerConfiguration());
        $builder->addConfiguration(new ResilienceConfiguration());
        $builder->addConfiguration(new SerializerConfiguration());
        $builder->addConfiguration(new HttpClientConfiguration());
        $builder->addConfiguration(new FoundationConfiguration());
        $builder->addConfiguration(new DiactorosHttpMessageFactoryConfiguration());
        foreach ($this->getConfigurations() as $configuration) {
            $builder->addConfiguration($configuration);
        }
        $app = Application::create(function () use ($builder) {
            return $builder->build();
        });
        $config = Application::getInstance()->getConfig();
        $config->merge($this->getConfig());
        $builder->addDefinitions([
            LoggerInterface::class => new Logger('test', [new ErrorLogHandler()]),
            PoolFactoryInterface::class => new PoolFactory(false),
            PropertyResolverInterface::class => $config,
            ServiceResolverInterface::class => new InMemoryServiceResolver(),
            AnnotationReaderInterface::class => AnnotationReader::getInstance(),
        ]);

        /** @var ReflectionNamespaceFactory $reflectionNs */
        $reflectionNs = ReflectionNamespaceFactory::getInstance();
        $reflectionNs->register(__NAMESPACE__.'\\fixtures', __DIR__.'/fixtures');
        $builder->setReflectionNamespaceFactory($reflectionNs);
        $builder->componentScan([__NAMESPACE__.'\\fixtures']);
        $builder->addDefinitions([
            LoggerInterface::class => \kuiper\logger\Logger::nullLogger(),
            LoggerFactoryInterface::class => factory(function (ContainerInterface $container) {
                return new LoggerFactory($container, [
                    'loggers' => [
                        'root' => ['console' => true],
                    ],
                ]);
            }),
            PoolFactoryInterface::class => new PoolFactory(false),
            PropertyResolverInterface::class => $config,
            AnnotationReaderInterface::class => AnnotationReader::getInstance(),
        ]);
        $builder->defer(function (ContainerInterface $container) {
            foreach ($this->getDefinitions() as $name => $definition) {
                $container->set($name, $definition);
            }
        });

        return $app->getContainer();
    }

    protected function getConfigurations(): array
    {
        return [];
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

    public function createRequest($req): ServerRequestInterface
    {
        [$method, $url] = explode(' ', $req, 2);
        $result = parse_url($url);
        if (isset($result['host'])) {
            $host = $result['host'].(isset($result['port']) ? ':'.$result['port'] : '');
        } else {
            $host = 'localhost';
        }

        return (new ServerRequestFactory())
            ->createServerRequest($method, sprintf('%s://%s%s', $result['scheme'] ?? 'http', $host, $result['path']));
    }
}
