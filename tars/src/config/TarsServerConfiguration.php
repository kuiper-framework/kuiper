<?php

declare(strict_types=1);

namespace kuiper\tars\config;

use function DI\factory;
use kuiper\di\annotation\Bean;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\Arrays;
use kuiper\helper\PropertyResolverInterface;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\server\Service;
use kuiper\rpc\ServiceLocator;
use kuiper\serializer\NormalizerInterface;
use kuiper\swoole\Application;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\event\ReceiveEvent;
use kuiper\swoole\ServerPort;
use kuiper\tars\annotation\TarsServant;
use kuiper\tars\server\Adapter;
use kuiper\tars\server\ClientProperties;
use kuiper\tars\server\ServerProperties;
use kuiper\tars\server\TarsServerFactory;
use kuiper\tars\server\TarsTcpReceiveEventListener;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class TarsServerConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function getDefinitions(): array
    {
        Application::getInstance()->getConfig()->merge([
            'application' => [
                'logging' => [
                    'logger' => [
                        AccessLog::class => 'AccessLogLogger',
                    ],
                ],
                'tars' => [
                    'server' => [
                        'middleware' => [
                            AccessLog::class,
                        ],
                    ],
                ],
                'listeners' => [
                    ReceiveEvent::class => TarsTcpReceiveEventListener::class,
                ],
            ],
        ]);

        return [
            TarsServerFactory::class => factory([TarsServerFactory::class, 'createFromContainer']),
            TarsTcpReceiveEventListener::class => factory([TarsServerFactory::class, 'createTcpReceiveEventListener']),
        ];
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
}
