<?php

declare(strict_types=1);

namespace kuiper\jsonrpc;

use function DI\factory;
use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\AwareInjection;
use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\helper\PropertyResolverInterface;
use kuiper\http\client\HttpClientConfiguration;
use kuiper\jsonrpc\config\JsonRpcClientConfiguration;
use kuiper\jsonrpc\config\JsonRpcHttpServerConfiguration;
use kuiper\logger\LoggerFactory;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\reflection\ReflectionNamespaceFactory;
use kuiper\serializer\SerializerConfiguration;
use kuiper\swoole\Application;
use kuiper\swoole\config\DiactorosHttpMessageFactoryConfiguration;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
        $builder->addConfiguration(new JsonRpcClientConfiguration());
        $builder->addConfiguration(new JsonRpcHttpServerConfiguration());
        $app = Application::create(function () use ($builder) {
            return $builder->build();
        });
        $config = Application::getInstance()->getConfig();
        $config->merge($configArr);
        $builder->addDefinitions(new PropertiesDefinitionSource($config));
        $builder->addConfiguration(new DiactorosHttpMessageFactoryConfiguration());
        $builder->addConfiguration(new HttpClientConfiguration());
        /** @var ReflectionNamespaceFactory $reflectionNs */
        $reflectionNs = ReflectionNamespaceFactory::getInstance();
        $reflectionNs->register(__NAMESPACE__.'\\fixtures', __DIR__.'/fixtures');
        $builder->setReflectionNamespaceFactory($reflectionNs);
        $builder->componentScan([__NAMESPACE__.'\\fixtures']);
        $builder->addAwareInjection(AwareInjection::create(LoggerAwareInterface::class));
        $builder->addDefinitions($this->getDefinitions());
        $builder->addDefinitions([
            LoggerInterface::class => new NullLogger(),
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
