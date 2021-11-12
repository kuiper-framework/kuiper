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

use DI\Annotation\Inject;
use function DI\autowire;
use function DI\factory;
use function DI\get;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\Configuration;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\Arrays;
use kuiper\helper\PropertyResolverInterface;
use kuiper\logger\LoggerConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\server\Service;
use kuiper\rpc\ServiceLocator;
use kuiper\serializer\NormalizerInterface;
use kuiper\swoole\Application;
use kuiper\swoole\config\ServerConfiguration;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\logger\RequestLogFormatterInterface;
use kuiper\swoole\ServerPort;
use kuiper\tars\annotation\TarsServant;
use kuiper\tars\core\TarsRequestLogFormatter;
use kuiper\tars\server\Adapter;
use kuiper\tars\server\AdminServantImpl;
use kuiper\tars\server\ClientProperties;
use kuiper\tars\server\listener\KeepAlive;
use kuiper\tars\server\listener\RequestStat;
use kuiper\tars\server\listener\ServiceMonitor;
use kuiper\tars\server\monitor\collector\ServiceMemoryCollector;
use kuiper\tars\server\monitor\collector\WorkerNumCollector;
use kuiper\tars\server\monitor\Monitor;
use kuiper\tars\server\monitor\MonitorInterface;
use kuiper\tars\server\servant\AdminServant;
use kuiper\tars\server\ServerProperties;
use kuiper\tars\server\stat\Stat;
use kuiper\tars\server\stat\StatInterface;
use kuiper\tars\server\stat\StatStore;
use kuiper\tars\server\stat\SwooleTableStatStore;
use kuiper\tars\server\TarsServerFactory;
use kuiper\tars\server\TarsTcpReceiveEventListener;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

/**
 * @Configuration(dependOn={ServerConfiguration::class})
 */
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
                        'monitors' => [
                            WorkerNumCollector::class,
                            ServiceMemoryCollector::class,
                        ],
                    ],
                ],
                'listeners' => [
                    KeepAlive::class,
                    RequestStat::class,
                    ServiceMonitor::class,
                    TarsTcpReceiveEventListener::class,
                ],
            ],
        ]);

        return [
            TarsServerFactory::class => factory([TarsServerFactory::class, 'createFromContainer']),
            StatStore::class => autowire(SwooleTableStatStore::class),
            StatInterface::class => autowire(Stat::class),
            AdminServant::class => autowire(AdminServantImpl::class)
                ->constructorParameter('tarsFilePath', Application::getInstance()->getBasePath().'/tars/servant'),
            MonitorInterface::class => autowire(Monitor::class)
                ->constructorParameter('collectors', get('monitorCollectors')),
            'tarsServerRequestLogFormatter' => autowire(TarsRequestLogFormatter::class),
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
        $adapters = Arrays::assoc(array_filter($serverProperties->getAdapters(), static function (Adapter $adapter): bool {
            return ServerType::TCP === $adapter->getServerType();
        }), 'servant');
        if (empty($adapters)) {
            return [];
        }
        $logger = $container->get(LoggerFactoryInterface::class)->create(__CLASS__);

        $this->registerAdminServant();
        /** @var TarsServant $annotation */
        foreach (ComponentCollection::getAnnotations(TarsServant::class) as $annotation) {
            $serviceImpl = $container->get($annotation->getComponentId());
            if (false !== strpos($annotation->service, '.')) {
                $servantName = $annotation->service;
            } else {
                $servantName = $serverProperties->getServerName().'.'.$annotation->service;
            }
            if (!isset($adapters[$servantName])) {
                $logger->warning(self::TAG."servant $servantName not defined in conf");
                continue;
            }
            $adapter = $adapters[$servantName];
            $endpoint = $adapter->getEndpoint();
            Assert::notNull($endpoint);
            $serverPort = new ServerPort($endpoint->getHost(), $endpoint->getPort(), $adapter->getServerType());

            $methods = Arrays::pull($annotation->getTarget()->getMethods(\ReflectionMethod::IS_PUBLIC), 'name');
            $services[$servantName] = new Service(
                new ServiceLocator($servantName),
                $serviceImpl,
                $methods,
                $serverPort
            );
            $logger->info(self::TAG."register servant $servantName listen on {$endpoint}");
        }

        return $services;
    }

    private function registerAdminServant(): void
    {
        foreach (ComponentCollection::getAnnotations(TarsServant::class) as $annotation) {
            if ('AdminObj' === $annotation->service) {
                return;
            }
        }
        $annotation = new TarsServant();
        $annotation->service = 'AdminObj';
        $annotation->setTarget(new \ReflectionClass(AdminServant::class));
        ComponentCollection::register($annotation);
    }

    /**
     * @Bean("monitorCollectors")
     */
    public function monitorCollectors(ContainerInterface $container): array
    {
        return array_map([$container, 'get'],
            Application::getInstance()->getConfig()->get('application.tars.server.monitors', []));
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
                        'TarsServerRequestLogger' => LoggerConfiguration::createJsonLogger(
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
