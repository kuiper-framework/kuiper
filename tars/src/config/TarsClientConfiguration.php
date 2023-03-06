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

namespace kuiper\tars\config;

use DI\Attribute\Inject;

use function DI\autowire;
use function DI\factory;

use kuiper\di\attribute\Bean;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\Arrays;
use kuiper\helper\Text;
use kuiper\logger\LoggerConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\resilience\core\SwooleAtomicCounter;
use kuiper\rpc\client\listener\RetryOnRetryRemoveEndpointListener;
use kuiper\rpc\client\middleware\Retry;
use kuiper\rpc\client\middleware\ServiceDiscovery;
use kuiper\rpc\client\RequestIdGenerator;
use kuiper\rpc\client\RequestIdGeneratorInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcRequestJsonLogFormatter;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\servicediscovery\ChainedServiceResolver;
use kuiper\rpc\servicediscovery\dns\DnsResolverInterface;
use kuiper\rpc\servicediscovery\dns\NetDns2Resolver;
use kuiper\rpc\servicediscovery\DnsServiceResolver;
use kuiper\rpc\servicediscovery\InMemoryCache;
use kuiper\rpc\servicediscovery\InMemoryServiceResolver;
use kuiper\rpc\servicediscovery\loadbalance\LoadBalanceAlgorithm;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\swoole\Application;
use kuiper\swoole\logger\RequestLogFormatterInterface;
use kuiper\swoole\pool\ConnectionProxyGenerator;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\tars\attribute\TarsClient;
use kuiper\tars\client\middleware\AddRequestReferer;
use kuiper\tars\client\middleware\RequestStat;
use kuiper\tars\client\TarsProxyFactory;
use kuiper\tars\client\TarsRegistryResolver;
use kuiper\tars\core\EndpointParser;
use kuiper\tars\core\TarsRequestJsonLogFormatter;
use kuiper\tars\integration\ConfigServant;
use kuiper\tars\integration\LogServant;
use kuiper\tars\integration\PropertyFServant;
use kuiper\tars\integration\QueryFServant;
use kuiper\tars\integration\ServerFServant;
use kuiper\tars\integration\StatFServant;
use Net_DNS2;
use Net_DNS2_Resolver;
use Psr\Container\ContainerInterface;
use ReflectionException;
use RuntimeException;

class TarsClientConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $this->addTarsRequestLog();
        $config = Application::getInstance()->getConfig();
        $middlewares = [
            Retry::class,
            AddRequestReferer::class,
            'tarsServiceDiscovery',
            'tarsClientRequestLog',
        ];
        if ($config->getBool('application.tars.client.enable_stat')) {
            $middlewares[] = RequestStat::class;
        }
        $config->merge([
            'application' => [
                'tars' => [
                    'client' => [
                        'middleware' => $middlewares,
                    ],
                ],
                'pool' => [
                    'tars.tarsregistry.QueryObj' => [
                        'aged_timeout' => 50,
                    ],
                ],
            ],
        ]);
        $config->merge([
            'application' => [
                'listeners' => [
                    'tarsRetryOnRetryRemoveEndpointListener',
                ],
            ],
        ]);

        return array_merge($this->createTarsClients(), [
            'tarsClientRequestLogFormatter' => autowire(TarsRequestJsonLogFormatter::class)
                ->constructorParameter('fields', RpcRequestJsonLogFormatter::CLIENT),
        ]);
    }

    #[Bean]
    public function requestIdGenerator(): RequestIdGeneratorInterface
    {
        return new RequestIdGenerator(new SwooleAtomicCounter());
    }

    #[Bean('tarsClientRequestLog')]
    public function tarsRequestLog(
        #[Inject('tarsClientRequestLogFormatter')] RequestLogFormatterInterface $requestLogFormatter,
        LoggerFactoryInterface $loggerFactory): AccessLog
    {
        $excludeRegexp = Application::getInstance()->getConfig()->getString('application.tars.client.log_excludes', '#^tars.tarsnode#');
        $middleware = new AccessLog($requestLogFormatter, static function (RpcRequestInterface $request) use ($excludeRegexp) {
            return !preg_match($excludeRegexp, $request->getRpcMethod()->getServiceLocator()->getName());
        });
        $middleware->setLogger($loggerFactory->create('TarsRequestLogger'));

        return $middleware;
    }

    /**
     * @throws ReflectionException
     */
    public function createTarsClient(TarsProxyFactory $factory, string $clientClass, string $name = null, array $options = []): object
    {
        $config = Application::getInstance()->getConfig();
        $clientOptions = $config->get('application.tars.client.options', []);
        $options = array_merge($options, $clientOptions['default'] ?? [], $clientOptions[$name ?? $clientClass] ?? []);

        return $factory->create($clientClass, $options);
    }

    private function createTarsClients(): array
    {
        $definitions = [];
        $config = Application::getInstance()->getConfig();
        /** @var TarsClient $annotation */
        foreach (ComponentCollection::getComponents(TarsClient::class) as $annotation) {
            $name = $annotation->getComponentId();
            $definitions[$name] = factory(function (TarsProxyFactory $factory) use ($annotation) {
                $options = Arrays::mapKeys(get_object_vars($annotation), [Text::class, 'snakeCase']);

                return $this->createTarsClient($factory, $annotation->getTargetClass(), $annotation->getComponentId(), $options);
            });
        }

        foreach (array_merge([
            ConfigServant::class,
            ServerFServant::class,
            LogServant::class,
            PropertyFServant::class,
            StatFServant::class,
            QueryFServant::class,
        ], $config->get('application.tars.client.clients', [])) as $name => $service) {
            $componentId = is_string($name) ? $name : $service;
            $definitions[$componentId] = factory(function (TarsProxyFactory $factory) use ($componentId, $service) {
                return $this->createTarsClient($factory, $service, $componentId);
            });
        }

        return $definitions;
    }

    #[Bean]
    public function tarsProxyFactory(ContainerInterface $container, #[Inject('tarsClientMiddlewares')] array $middlewares): TarsProxyFactory
    {
        return TarsProxyFactory::createFromContainer($container, $middlewares);
    }

    #[Bean('tarsClientMiddlewares')]
    public function tarsClientMiddlewares(ContainerInterface $container): array
    {
        $middlewares = [];
        foreach (Application::getInstance()->getConfig()->get('application.tars.client.middleware', []) as $middleware) {
            if (is_string($middleware)) {
                $middleware = $container->get($middleware);
            }
            $middlewares[] = $middleware;
        }

        return $middlewares;
    }

    #[Bean('tarsServiceDiscovery')]
    public function tarsServiceDiscovery(
        #[Inject('tarsServiceResolver')] ServiceResolverInterface $serviceResolver,
        #[Inject('application.client.service_discovery.load_balance')] ?string $loadBalance): ServiceDiscovery
    {
        $lb = null === $loadBalance ? LoadBalanceAlgorithm::ROUND_ROBIN : LoadBalanceAlgorithm::from($loadBalance);

        return new ServiceDiscovery($serviceResolver, new InMemoryCache(), $lb);
    }

    #[Bean('tarsServiceResolver')]
    public function tarsServiceResolver(ContainerInterface $container): ServiceResolverInterface
    {
        $resolvers = [
            $container->get(InMemoryServiceResolver::class),
        ];
        if ((bool) $container->get('application.tars.client.locator')) {
            $resolvers[] = $container->get(TarsRegistryResolver::class);
        }
        if ((bool) $container->get('application.client.service_discovery.enable_dns')) {
            $resolvers[] = $container->get(DnsServiceResolver::class);
        }

        return new ChainedServiceResolver($resolvers);
    }

    #[Bean]
    public function dnsResolver(PoolFactoryInterface $poolFactory): DnsResolverInterface
    {
        if (!class_exists(Net_DNS2::class)) {
            throw new RuntimeException('Net_DNS2 is required, please run composer require pear/net_dns2');
        }
        /** @var Net_DNS2_Resolver $resolver */
        $resolver = ConnectionProxyGenerator::create($poolFactory, Net_DNS2_Resolver::class, static function () {
            return new Net_DNS2_Resolver();
        });

        return new NetDns2Resolver($resolver);
    }

    #[Bean]
    public function tarsRegistryResolver(ContainerInterface $container): TarsRegistryResolver
    {
        /** @var QueryFServant $queryClient */
        $queryClient = TarsProxyFactory::createFromContainer($container, [
            $container->get(Retry::class),
            new ServiceDiscovery($container->get(InMemoryServiceResolver::class), new InMemoryCache(), LoadBalanceAlgorithm::ROUND_ROBIN),
            $container->get('tarsClientRequestLog'),
        ])->create(QueryFServant::class);

        return new TarsRegistryResolver($queryClient);
    }

    #[Bean]
    public function inMemoryServiceResolver(): InMemoryServiceResolver
    {
        $config = Application::getInstance()->getConfig();
        $endpoints = [];
        foreach ($config->get('application.tars.client.options', []) as $name => $value) {
            if (isset($value['endpoint']) && preg_match('#^(\w+\.)+\w+@#', $value['endpoint'])) {
                $endpoints[] = $value['endpoint'];
            }
        }
        $endpoints[] = $config->getString('application.tars.client.locator');
        $endpoints[] = $config->getString('application.tars.server.node');

        $serviceEndpoints = array_values(array_map([EndpointParser::class, 'parseServiceEndpoint'], array_filter($endpoints)));

        return InMemoryServiceResolver::create($serviceEndpoints);
    }

    #[Bean('tarsRetryOnRetryRemoveEndpointListener')]
    public function retryOnRetryRemoveEndpointListener(
        #[Inject('tarsServiceDiscovery')] ServiceDiscovery $serviceDiscovery): RetryOnRetryRemoveEndpointListener
    {
        return new RetryOnRetryRemoveEndpointListener($serviceDiscovery);
    }

    private function addTarsRequestLog(): void
    {
        $config = Application::getInstance()->getConfig();
        $path = $config->get('application.logging.path');
        if (null === $path) {
            return;
        }
        $config->mergeIfNotExists([
            'application' => [
                'tars' => [
                    'client' => [
                        'log_file' => '{application.logging.path}/tars-client.json',
                    ],
                ],
                'logging' => [
                    'loggers' => [
                        'TarsRequestLogger' => LoggerConfiguration::createJsonLogger('{application.tars.client.log_file}'),
                    ],
                    'logger' => [
                        'TarsRequestLogger' => 'TarsRequestLogger',
                    ],
                ],
            ],
        ]);
    }
}
