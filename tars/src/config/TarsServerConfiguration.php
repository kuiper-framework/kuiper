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
use kuiper\helper\PropertyResolverInterface;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\rpc\JsonRpcRequestLogFormatter;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\server\Service;
use kuiper\rpc\ServiceLocator;
use kuiper\serializer\NormalizerInterface;
use kuiper\swoole\Application;
use kuiper\swoole\config\ServerConfiguration;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\event\ReceiveEvent;
use kuiper\swoole\ServerPort;
use kuiper\tars\annotation\TarsServant;
use kuiper\tars\server\Adapter;
use kuiper\tars\server\ClientProperties;
use kuiper\tars\server\listener\KeepAlive;
use kuiper\tars\server\listener\RequestStat;
use kuiper\tars\server\listener\ServiceMonitor;
use kuiper\tars\server\ServerProperties;
use kuiper\tars\server\stat\Stat;
use kuiper\tars\server\stat\StatInterface;
use kuiper\tars\server\stat\StatStore;
use kuiper\tars\server\stat\SwooleTableStatStore;
use kuiper\tars\server\TarsServerFactory;
use kuiper\tars\server\TarsTcpReceiveEventListener;
use kuiper\web\RequestLogFormatterInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class TarsServerConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function getDefinitions(): array
    {
        $this->addTarsRequestLog();
        Application::getInstance()->getConfig()->merge([
            'application' => [
                'tars' => [
                    'server' => [
                        'middleware' => [
                            'tarsServerRequestLog',
                        ],
                    ],
                ],
                'listeners' => [
                    KeepAlive::class,
                    RequestStat::class,
                    ServiceMonitor::class,
                    ReceiveEvent::class => TarsTcpReceiveEventListener::class,
                ],
            ],
        ]);

        return [
            TarsServerFactory::class => factory([TarsServerFactory::class, 'createFromContainer']),
            StatStore::class => autowire(SwooleTableStatStore::class),
            StatInterface::class => autowire(Stat::class),
            'tarsServerRequestLogFormatter' => autowire(JsonRpcRequestLogFormatter::class),
            TarsTcpReceiveEventListener::class => factory([TarsServerFactory::class, 'createTcpReceiveEventListener']),
        ];
    }

    /**
     * @Bean("tarsServerRequestLog")
     * @Inject({"requestLogFormatter": "tarsServerRequestLogFormatter"})
     */
    public function tarsServerRequestLog(RequestLogFormatterInterface $requestLogFormatter, LoggerFactoryInterface $loggerFactory): AccessLog
    {
        $middleware = new AccessLog($requestLogFormatter);
        $middleware->setLogger($loggerFactory->create('TarsServerRequestLogger'));

        return $middleware;
    }

    /**
     * @Bean("tarsServices")
     *
     * @return Service[]
     */
    public function tarsServices(ContainerInterface $container, ServerProperties $serverProperties): array
    {
        $services = [];
        /** @var Adapter[] $adapters */
        $adapters = array_values(array_filter($serverProperties->getAdapters(), static function (Adapter $adapter): bool {
            return ServerType::TCP === $adapter->getServerType();
        }));
        if (empty($adapters)) {
            return [];
        }
        $adapter = $adapters[0];
        $serverPort = new ServerPort($adapter->getEndpoint()->getHost(), $adapter->getEndpoint()->getPort(), $adapter->getServerType());
        $logger = $container->get(LoggerInterface::class);
        /** @var TarsServant $annotation */
        foreach (ComponentCollection::getAnnotations(TarsServant::class) as $annotation) {
            $serviceImpl = $container->get($annotation->getComponentId());
            $servantName = $serverProperties->getServerName().'.'.$annotation->service;
            $methods = Arrays::pull($annotation->getTarget()->getMethods(\ReflectionMethod::IS_PUBLIC), 'name');
            $services[$servantName] = new Service(
                new ServiceLocator($servantName),
                $serviceImpl,
                $methods,
                $serverPort
            );
            $logger->info(self::TAG."register servant $servantName");
        }

        return $services;
    }

    /**
     * @Bean("tarsServerMiddlewares")
     */
    public function tarsServerMiddlewares(ContainerInterface $container): array
    {
        $middlewares = [];
        foreach (Application::getInstance()->getConfig()->get('application.tars.server.middleware', []) as $middleware) {
            $middlewares[] = $container->get($middleware);
        }

        return $middlewares;
    }

    /**
     * @Bean
     */
    public function serverProperties(NormalizerInterface $normalizer, PropertyResolverInterface $config): ServerProperties
    {
        return $normalizer->denormalize($config->get('application.tars.server'), ServerProperties::class);
    }

    /**
     * @Bean
     */
    public function clientProperties(NormalizerInterface $normalizer, PropertyResolverInterface $config): ClientProperties
    {
        return $normalizer->denormalize($config->get('application.tars.client'), ClientProperties::class);
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
                        'TarsServerRequestLogger' => ServerConfiguration::createAccessLogger(
                            $config->getString('application.logging.tars_server_log_file', $path.'/tars-server.log')),
                    ],
                    'logger' => [
                        'TarsServerRequestLogger' => 'TarsServerRequestLogger',
                    ],
                ],
            ],
        ]);
    }
}
