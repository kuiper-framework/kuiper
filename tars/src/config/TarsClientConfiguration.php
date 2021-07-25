<?php

declare(strict_types=1);

namespace kuiper\tars\config;

use DI\Annotation\Inject;
use function DI\autowire;
use DI\Container;
use function DI\factory;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\cache\ArrayCache;
use kuiper\cache\ChainedCache;
use kuiper\di\annotation\Bean;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\Arrays;
use kuiper\helper\Text;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\rpc\client\ProxyGeneratorInterface;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\transporter\CachedServiceResolver;
use kuiper\rpc\transporter\ChainedServiceResolver;
use kuiper\rpc\transporter\InMemoryServiceRegistry;
use kuiper\rpc\transporter\ServiceResolverInterface;
use kuiper\rpc\transporter\SwooleTableServiceEndpointCache;
use kuiper\swoole\Application;
use kuiper\swoole\config\ServerConfiguration;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\tars\annotation\TarsClient;
use kuiper\tars\client\TarsProxyFactory;
use kuiper\tars\client\TarsProxyGenerator;
use kuiper\tars\client\TarsRegistryServiceResolver;
use kuiper\tars\core\EndpointParser;
use kuiper\tars\core\TarsMethodFactory;
use kuiper\tars\integration\QueryFServant;
use kuiper\web\LineRequestLogFormatter;
use kuiper\web\RequestLogFormatterInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

class TarsClientConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $this->addTarsRequestLog();
        $this->containerBuilder->defer(function (ContainerInterface $container): void {
            $this->createTarsClients($container);
        });

        return [
            RequestLogFormatterInterface::class => autowire(LineRequestLogFormatter::class),
            'tarsMethodFactory' => autowire(TarsMethodFactory::class),
        ];
    }

    /**
     * @Bean("tarsProxyGenerator")
     */
    public function tarsProxyGenerator(ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory): ProxyGeneratorInterface
    {
        return new TarsProxyGenerator($reflectionDocBlockFactory);
    }

    /**
     * @Bean("tarsRequestLog")
     */
    public function tarsRequestLog(RequestLogFormatterInterface $requestLogFormatter, LoggerFactoryInterface $loggerFactory): AccessLog
    {
        $middleware = new AccessLog($requestLogFormatter);
        $middleware->setLogger($loggerFactory->create('TarsRequestLogger'));

        return $middleware;
    }

    private function createTarsClients(ContainerInterface $container): void
    {
        /** @var TarsClient $annotation */
        foreach (ComponentCollection::getAnnotations(TarsClient::class) as $annotation) {
            /** @var Container $container */
            $container->set($annotation->getTargetClass(), factory(function () use ($container, $annotation) {
                $options = array_merge(
                    Arrays::mapKeys(get_object_vars($annotation), [Text::class, 'snakeCase']),
                    Application::getInstance()->getConfig()
                        ->get('application.tars.client.options', [])[$annotation->value] ?? []
                );

                return $container->get(TarsProxyFactory::class)->create($annotation->getTargetClass(), $options);
            }));
        }
    }

    /**
     * @Bean
     * @Inject({"serviceResolver": "tarsRegistryServiceResolver", "middlewares": "tarsClientMiddlewares"})
     */
    public function tarsProxyFactory(
        ServiceResolverInterface $serviceResolver,
        AnnotationReaderInterface $annotationReader,
        PoolFactoryInterface $poolFactory,
        LoggerFactoryInterface $loggerFactory,
        array $middlewares): TarsProxyFactory
    {
        $tarsProxyFactory = new TarsProxyFactory($serviceResolver, $annotationReader);
        $tarsProxyFactory->setLoggerFactory($loggerFactory);
        $tarsProxyFactory->setPoolFactory($poolFactory);
        $tarsProxyFactory->setMiddlewares($middlewares);

        return $tarsProxyFactory;
    }

    public function createClient(string $clientInterfaceClass, array $options = [])
    {
        $tarsProxyFactory = new TarsProxyFactory(new TarsRegistryServiceResolver($this->tarsRegistryClient()));

        return $tarsProxyFactory->create($clientInterfaceClass, $options);
    }

    public function tarsRegistryClient(): QueryFServant
    {
        $tarsProxyFactory = new TarsProxyFactory($this->inMemoryServiceRegistry());

        return $tarsProxyFactory->create(QueryFServant::class);
    }

    /**
     * @Bean("tarsRegistryServiceResolver")
     * @Inject({"cache": "tarsServiceEndpointCache"})
     */
    public function tarsRegistryServiceResolver(QueryFServant $queryFServant, CacheInterface $cache): ServiceResolverInterface
    {
        return new ChainedServiceResolver([
            $this->inMemoryServiceRegistry(),
            new CachedServiceResolver(new TarsRegistryServiceResolver($queryFServant), $cache),
        ]);
    }

    /**
     * @Bean("tarsServiceEndpointCache")
     * @Inject({"options": "application.tars.client.registry"})
     */
    public function tarsServiceEndpointCache(?array $options): CacheInterface
    {
        $ttl = $options['ttl'] ?? 60;
        $capacity = $options['capacity'] ?? 256;
        $registryCache = new SwooleTableServiceEndpointCache($ttl, $capacity, $options['size'] ?? 2048);

        return new ChainedCache([
            new ArrayCache($options['memory-ttl'] ?? 1, $capacity),
            $registryCache,
        ]);
    }

    /**
     * @Bean
     */
    public function inMemoryServiceRegistry(): InMemoryServiceRegistry
    {
        $config = Application::getInstance()->getConfig();
        $registry = new InMemoryServiceRegistry();
        if ($config->has('application.tars.client.locator')) {
            $registry->registerService(EndpointParser::parseServiceEndpoint(
                $config->getString('application.tars.client.locator')));
        }
        if ($config->has('application.tars.server.node')) {
            $registry->registerService(EndpointParser::parseServiceEndpoint(
                $config->getString('application.tars.server.node')));
        }
        foreach ($config->get('application.tars.client.routes', []) as $str) {
            $registry->registerService(EndpointParser::parseServiceEndpoint($str));
        }

        return $registry;
    }

    /**
     * @Bean("tarsClientMiddlewares")
     */
    public function tarsClientMiddlewares(ContainerInterface $container): array
    {
        $middlewares = [];
        foreach (Application::getInstance()->getConfig()->get('application.tars.client.middleware', []) as $middleware) {
            $middlewares[] = $container->get($middleware);
        }

        return $middlewares;
    }

    private function addTarsRequestLog(): void
    {
        $serverConfiguration = new ServerConfiguration();
        $config = Application::getInstance()->getConfig();
        $path = $config->get('application.logging.path');
        if (null === $path) {
            return;
        }
        $config->mergeIfNotExists([
            'application' => [
                'logging' => [
                    'loggers' => [
                        'TarsRequestLogger' => $serverConfiguration->createAccessLogger($path.'/tars-client.log'),
                    ],
                    'logger' => [
                        'TarsRequestLogger' => 'TarsRequestLogger',
                    ],
                ],
                'jsonrpc' => [
                    'client' => [
                        'middleware' => [
                            'tarsRequestLog',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
