<?php

declare(strict_types=1);

namespace kuiper\tars\config;

use DI\Annotation\Inject;
use function DI\autowire;
use function DI\factory;
use kuiper\di\annotation\Bean;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\Arrays;
use kuiper\helper\Text;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\rpc\client\middleware\ServiceDiscovery;
use kuiper\rpc\JsonRpcRequestLogFormatter;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\servicediscovery\ChainedServiceResolver;
use kuiper\rpc\servicediscovery\InMemoryServiceResolver;
use kuiper\rpc\servicediscovery\loadbalance\LoadBalanceAlgorithm;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\swoole\Application;
use kuiper\swoole\config\ServerConfiguration;
use kuiper\tars\annotation\TarsClient;
use kuiper\tars\client\middleware\RequestStat;
use kuiper\tars\client\TarsProxyFactory;
use kuiper\tars\client\TarsRegistryResolver;
use kuiper\tars\core\EndpointParser;
use kuiper\tars\integration\ConfigServant;
use kuiper\tars\integration\LogServant;
use kuiper\tars\integration\PropertyFServant;
use kuiper\tars\integration\QueryFServant;
use kuiper\tars\integration\ServerFServant;
use kuiper\tars\integration\StatFServant;
use kuiper\web\RequestLogFormatterInterface;
use Psr\Container\ContainerInterface;

class TarsClientConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $this->addTarsRequestLog();
        $config = Application::getInstance()->getConfig();
        $middlewares = [
            'tarsServiceDiscovery',
            'tarsClientRequestLog',
        ];
        if ($config->has('application.tars.server.node')) {
            $middlewares[] = RequestStat::class;
        }
        $config->merge([
            'application' => [
                'tars' => [
                    'client' => [
                        'middleware' => $middlewares,
                    ],
                ],
            ],
        ]);

        return array_merge($this->createTarsClients(), [
            'tarsClientRequestLogFormatter' => autowire(JsonRpcRequestLogFormatter::class)
                ->constructorParameter('fields', JsonRpcRequestLogFormatter::CLIENT),
        ]);
    }

    /**
     * @Bean("tarsClientRequestLog")
     * @Inject({"requestLogFormatter": "tarsClientRequestLogFormatter"})
     */
    public function tarsRequestLog(RequestLogFormatterInterface $requestLogFormatter, LoggerFactoryInterface $loggerFactory): AccessLog
    {
        $middleware = new AccessLog($requestLogFormatter);
        $middleware->setLogger($loggerFactory->create('TarsRequestLogger'));

        return $middleware;
    }

    private function createTarsClients(): array
    {
        $definitions = [];
        $config = Application::getInstance()->getConfig();
        $options = $config->get('application.jsonrpc.client.options', []);
        $createClient = static function (ContainerInterface $container, array $options) {
            if (isset($options['middleware'])) {
                foreach ($options['middleware'] as $i => $middleware) {
                    if (is_string($middleware)) {
                        $options['middleware'][$i] = $container->get($middleware);
                    }
                }
            }

            return $container->get(TarsProxyFactory::class)->create($options['class'], $options);
        };
        /** @var TarsClient $annotation */
        foreach (ComponentCollection::getAnnotations(TarsClient::class) as $annotation) {
            $name = $annotation->getComponentId();
            $clientOptions = array_merge(
                Arrays::mapKeys(get_object_vars($annotation), [Text::class, 'snakeCase']),
                $options[$name] ?? []
            );
            $clientOptions['class'] = $annotation->getTargetClass();
            $definitions[$name] = factory(function (ContainerInterface $container) use ($createClient, $clientOptions) {
                return $createClient($container, $clientOptions);
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
            $clientOptions = array_merge($options[$componentId] ?? []);
            $clientOptions['class'] = $service;
            $definitions[$componentId] = factory(function (ContainerInterface $container) use ($createClient, $clientOptions) {
                return $createClient($container, $clientOptions);
            });
        }

        return $definitions;
    }

    /**
     * @Bean
     * @Inject({"middlewares": "tarsClientMiddlewares"})
     */
    public function tarsProxyFactory(ContainerInterface $container, array $middlewares): TarsProxyFactory
    {
        return TarsProxyFactory::createFromContainer($container, $middlewares);
    }

    /**
     * @Bean("tarsClientMiddlewares")
     */
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

    /**
     * @Bean("tarsServiceDiscovery")
     * @Inject({
     *     "serviceResolver": "tarsServiceResolver",
     *     "loadBalance": "application.client.service_disovery.load_balance"
     * })
     */
    public function tarsServiceDiscovery(ServiceResolverInterface $serviceResolver, ?string $loadBalance): ServiceDiscovery
    {
        return new ServiceDiscovery($serviceResolver, null, $loadBalance ?? LoadBalanceAlgorithm::ROUND_ROBIN);
    }

    /**
     * @Bean("tarsServiceResolver")
     */
    public function tarsServiceResolver(ContainerInterface $container): ServiceResolverInterface
    {
        return new ChainedServiceResolver([
            $container->get(InMemoryServiceResolver::class),
            $container->get(TarsRegistryResolver::class),
        ]);
    }

    /**
     * @Bean
     */
    public function tarsRegistryResolver(ContainerInterface $container): TarsRegistryResolver
    {
        /** @var QueryFServant $queryClient */
        $queryClient = TarsProxyFactory::createFromContainer($container, [
            new ServiceDiscovery($container->get(InMemoryServiceResolver::class)),
        ])->create(QueryFServant::class);

        return new TarsRegistryResolver($queryClient);
    }

    /**
     * @Bean
     */
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

    private function addTarsRequestLog(): void
    {
        $config = Application::getInstance()->getConfig();
        $path = $config->get('application.logging.path');
        if (null === $path) {
            return;
        }
        $config->merge([
            'application' => [
                'logging' => [
                    'loggers' => [
                        'TarsRequestLogger' => ServerConfiguration::createAccessLogger(
                            $config->getString('application.logging.tars_client_log_file', $path.'/tars-client.log')),
                    ],
                    'logger' => [
                        'TarsRequestLogger' => 'TarsRequestLogger',
                    ],
                ],
            ],
        ]);
    }
}
