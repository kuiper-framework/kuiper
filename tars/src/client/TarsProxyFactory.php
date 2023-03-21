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

namespace kuiper\tars\client;

use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use kuiper\di\ContainerAwareInterface;
use kuiper\di\ContainerAwareTrait;
use kuiper\logger\Logger;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\resilience\core\SimpleCounter;
use kuiper\rpc\client\middleware\ServiceDiscovery;
use kuiper\rpc\client\ProxyGeneratorInterface;
use kuiper\rpc\client\RequestIdGenerator;
use kuiper\rpc\client\RequestIdGeneratorInterface;
use kuiper\rpc\client\RpcClient;
use kuiper\rpc\client\RpcExecutorFactory;
use kuiper\rpc\client\RpcExecutorFactoryInterface;
use kuiper\rpc\client\RpcRequestFactoryInterface;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\servicediscovery\ChainedServiceResolver;
use kuiper\rpc\servicediscovery\InMemoryCache;
use kuiper\rpc\servicediscovery\InMemoryServiceResolver;
use kuiper\rpc\servicediscovery\loadbalance\LoadBalanceAlgorithm;
use kuiper\rpc\servicediscovery\ServiceEndpoint;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\rpc\transporter\Endpoint;
use kuiper\rpc\transporter\PooledTransporter;
use kuiper\rpc\transporter\SwooleCoroutineTcpTransporter;
use kuiper\rpc\transporter\SwooleTcpTransporter;
use kuiper\rpc\transporter\TransporterInterface;
use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\tars\attribute\TarsClient;
use kuiper\tars\core\EndpointParser;
use kuiper\tars\core\TarsMethodFactory;
use kuiper\tars\integration\QueryFServant;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use ReflectionClass;
use ReflectionException;

class TarsProxyFactory implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param MiddlewareInterface[] $middlewares
     */
    final public function __construct(
        private readonly RequestFactoryInterface $httpRequestFactory,
        private readonly ResponseFactoryInterface $httpResponseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory,
        private readonly PoolFactoryInterface $poolFactory,
        private readonly RequestIdGeneratorInterface $requestIdGenerator,
        private readonly ?LoggerFactoryInterface $loggerFactory,
        private readonly array $middlewares = [],
        private readonly array $options = []
    ) {
    }

    public static function createFromContainer(ContainerInterface $container, array $middlewares): self
    {
        $tarsProxyFactory = new static(
            $container->get(RequestFactoryInterface::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(ReflectionDocBlockFactoryInterface::class),
            $container->get(PoolFactoryInterface::class),
            $container->get(RequestIdGeneratorInterface::class),
            $container->get(LoggerFactoryInterface::class),
            $middlewares,
            $container->get('application.tars.client.options') ?? []
        );
        $tarsProxyFactory->setContainer($container);

        return $tarsProxyFactory;
    }

    /**
     * @param string|ServiceEndpoint... $serviceEndpoints
     *
     * @return self
     *
     * @throws ReflectionException
     */
    public static function createDefault(...$serviceEndpoints): self
    {
        $resolver = InMemoryServiceResolver::create(array_map(static function ($endpoint): ServiceEndpoint {
            return is_string($endpoint) ? EndpointParser::parseServiceEndpoint($endpoint) : $endpoint;
        }, $serviceEndpoints));
        /** @var QueryFServant $queryFClient */
        $queryFClient = self::createWithResolver($resolver)->create(QueryFServant::class);

        return self::createWithResolver(new ChainedServiceResolver([
            $resolver,
            new TarsRegistryResolver($queryFClient),
        ]));
    }

    private static function createWithResolver(ServiceResolverInterface $serviceResolver): self
    {
        if (class_exists(Psr17Factory::class)) {
            $factory = new Psr17Factory();
            $httpRequestFactory = $factory;
            $httpResponseFactory = $factory;
            $streamFactory = $factory;
        } elseif (class_exists(HttpFactory::class)) {
            $factory = new HttpFactory();
            $httpRequestFactory = $factory;
            $httpResponseFactory = $factory;
            $streamFactory = $factory;
        } elseif (class_exists(RequestFactory::class)) {
            $httpRequestFactory = new RequestFactory();
            $httpResponseFactory = new ResponseFactory();
            $streamFactory = new StreamFactory();
        } else {
            throw new InvalidArgumentException('Cannot find ');
        }

        return new static(
            $httpRequestFactory,
            $httpResponseFactory,
            $streamFactory,
            ReflectionDocBlockFactory::getInstance(),
            new PoolFactory(),
            new RequestIdGenerator(new SimpleCounter()),
            null,
            [new ServiceDiscovery($serviceResolver, new InMemoryCache(), LoadBalanceAlgorithm::ROUND_ROBIN)]
        );
    }

    protected function createProxyGenerator(): ProxyGeneratorInterface
    {
        return new TarsProxyGenerator($this->reflectionDocBlockFactory);
    }

    protected function createRpcRequestFactory(array $options): RpcRequestFactoryInterface
    {
        return new TarsRequestFactory(
            $this->httpRequestFactory,
            $this->streamFactory,
            new TarsMethodFactory($options),
            $this->requestIdGenerator,
            $options['endpoint'] ?? '/'
        );
    }

    protected function createRpcClient(string $className, array $options): RpcRequestHandlerInterface
    {
        $transporter = new PooledTransporter($this->poolFactory->create($options['service'], function (int $connId) use ($className, $options): TransporterInterface {
            if (Coroutine::isEnabled()) {
                $transporter = new SwooleCoroutineTcpTransporter($this->httpResponseFactory, $options);
            } else {
                $transporter = new SwooleTcpTransporter($this->httpResponseFactory, $options);
            }
            $logger = null !== $this->loggerFactory ? $this->loggerFactory->create($className) : Logger::nullLogger();
            $transporter->setLogger($logger);

            return $transporter;
        }));

        return new RpcClient($transporter, new TarsResponseFactory());
    }

    protected function createRpcExecutorFactory(string $className, array $options): RpcExecutorFactoryInterface
    {
        return new RpcExecutorFactory(
            $this->createRpcRequestFactory($options),
            $this->createRpcClient($className, $options),
            array_merge($this->middlewares, $options['middleware'] ?? [])
        );
    }

    private function envOptions(string $componentId): array
    {
        $prefix = 'TARS_CLIENT_'.str_replace(['.', '\\'], '_', strtoupper($componentId)).'__';
        $options = [];
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $name = strtolower(substr($key, strlen($prefix)));
                $options[$name] = $value;
            }
        }

        return $options;
    }

    /**
     * @param string $className
     * @param array  $options
     *
     * @return object
     *
     * @throws ReflectionException
     */
    public function create(string $className, array $options = [])
    {
        $clientOptions = array_merge($this->options['default'] ?? [], $this->envOptions($className), $this->options[$className] ?? []);
        if (isset($options['name'])) {
            $clientOptions = array_merge($clientOptions, $this->envOptions($options['name']), $this->options[$options['name']] ?? []);
        }
        $options = array_merge($clientOptions, $options);
        $class = new ReflectionClass($className);
        if (isset($options['endpoint'])) {
            if (preg_match('#^\w+://#', $options['endpoint'])) {
                $options['endpoint'] = Endpoint::removeTcpScheme($options['endpoint']);
            } else {
                $serviceEndpoint = EndpointParser::parseServiceEndpoint($options['endpoint']);
                $options['service'] = $serviceEndpoint->getServiceName();
                $endpoints = array_values($serviceEndpoint->getEndpoints());
                if (count($endpoints) > 1) {
                    unset($options['endpoint']);
                } else {
                    $options['endpoint'] = Endpoint::removeTcpScheme((string) $endpoints[0]);
                }
            }
        }
        if (empty($options['service'])) {
            $attributes = $class->getAttributes(TarsClient::class);
            if (count($attributes) > 0) {
                /** @var TarsClient $tarsClient */
                $tarsClient = $attributes[0]->newInstance();
                $options['service'] = $tarsClient->getService();
            } else {
                throw new InvalidArgumentException('Cannot resolver service name');
            }
        }
        if ($class->isInterface()) {
            $proxyClass = $this->createProxyGenerator()->generate($className, $options);
            $proxyClass->eval();
            $className = $proxyClass->getClassName();
        }
        if (isset($options['middleware'])) {
            foreach ($options['middleware'] as $i => $middleware) {
                if (is_string($middleware) && null !== $this->container) {
                    $options['middleware'][$i] = $this->container->get($middleware);
                }
            }
        }

        return new $className($this->createRpcExecutorFactory($className, $options));
    }
}
