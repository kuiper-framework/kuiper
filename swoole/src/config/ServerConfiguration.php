<?php

declare(strict_types=1);

namespace kuiper\swoole\config;

use DI\Annotation\Inject;
use function DI\autowire;
use kuiper\di\annotation\Bean;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\event\EventListenerInterface;
use kuiper\event\EventSubscriberInterface;
use kuiper\helper\Properties;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\Application;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\event\ManagerStartEvent;
use kuiper\swoole\event\StartEvent;
use kuiper\swoole\event\WorkerStartEvent;
use kuiper\swoole\http\HttpMessageFactoryHolder;
use kuiper\swoole\http\SwooleRequestBridgeInterface;
use kuiper\swoole\http\SwooleResponseBridge;
use kuiper\swoole\http\SwooleResponseBridgeInterface;
use kuiper\swoole\listener\ManagerStartEventListener;
use kuiper\swoole\listener\StartEventListener;
use kuiper\swoole\listener\TaskEventListener;
use kuiper\swoole\listener\WorkerStartEventListener;
use kuiper\swoole\monolog\CoroutineIdProcessor;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\ServerCommand;
use kuiper\swoole\ServerConfig;
use kuiper\swoole\ServerFactory;
use kuiper\swoole\ServerPort;
use kuiper\web\LineRequestLogFormatter;
use kuiper\web\middleware\AccessLog;
use kuiper\web\RequestLogFormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ServerConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $config = Application::getInstance()->getConfig();
        $basePath = Application::getInstance()->getBasePath();
        $config->mergeIfNotExists([
            'application' => [
                'name' => 'app',
                'base_path' => $basePath,
                'default_command' => ServerCommand::NAME,
                'commands' => [
                    ServerCommand::NAME => ServerCommand::class,
                ],
                'logging' => [
                    'path' => $basePath.'/logs',
                ],
            ],
        ]);
        if (!$config->has('application.server.ports')) {
            $config->set('application.server.ports', ['8000' => ServerType::HTTP]);
        }
        $this->addAccessLoggerConfig($config);
        $this->addEventListeners($config);

        return [
            SwooleResponseBridgeInterface::class => autowire(SwooleResponseBridge::class),
            RequestLogFormatterInterface::class => autowire(LineRequestLogFormatter::class),
        ];
    }

    /**
     * @Bean()
     */
    public function server(
        ContainerInterface $container,
        ServerConfig $serverConfig,
        EventDispatcherInterface $eventDispatcher,
        LoggerFactoryInterface $loggerFactory): ServerInterface
    {
        $config = Application::getInstance()->getConfig();
        $serverFactory = new ServerFactory($loggerFactory->create(ServerFactory::class));
        $serverFactory->setEventDispatcher($eventDispatcher);
        $serverFactory->enablePhpServer($config->getBool('application.server.enable_php_server'));
        if ($serverConfig->getPort()->isHttpProtocol()) {
            $serverFactory->setHttpMessageFactoryHolder($container->get(HttpMessageFactoryHolder::class));
            $serverFactory->setSwooleRequestBridge($container->get(SwooleRequestBridgeInterface::class));
            $serverFactory->setSwooleResponseBridge($container->get(SwooleResponseBridgeInterface::class));
        }

        return $serverFactory->create($serverConfig);
    }

    /**
     * @Bean()
     * @Inject({"name": "applicationName"})
     */
    public function serverConfig(string $name): ServerConfig
    {
        $config = Application::getInstance()->getConfig();
        $settings = $config->get('application.swoole');
        $settings = array_merge([
            ServerSetting::OPEN_LENGTH_CHECK => true,
            ServerSetting::PACKAGE_LENGTH_TYPE => 'N',
            ServerSetting::PACKAGE_LENGTH_OFFSET => 0,
            ServerSetting::PACKAGE_BODY_OFFSET => 0,
            ServerSetting::MAX_WAIT_TIME => 60,
            ServerSetting::RELOAD_ASYNC => true,
            ServerSetting::PACKAGE_MAX_LENGTH => 10485760,
            ServerSetting::OPEN_TCP_NODELAY => true,
            ServerSetting::OPEN_EOF_CHECK => false,
            ServerSetting::OPEN_EOF_SPLIT => false,
            ServerSetting::DISPATCH_MODE => 2,
            ServerSetting::DAEMONIZE => false,
        ], $settings);

        $ports = [];
        foreach ($config->get('application.server.ports') as $port => $portConfig) {
            if (isset($ports[$port])) {
                throw new \InvalidArgumentException("Port $port was duplicated");
            }
            if (is_string($portConfig)) {
                $portConfig = [
                    'protocol' => $portConfig,
                ];
            }
            $ports[$port] = new ServerPort(
                $portConfig['host'] ?? '0.0.0.0',
                (int) $port,
                $portConfig['protocol'] ?? ServerType::HTTP,
                $portConfig
            );
        }

        $serverConfig = new ServerConfig($name, $settings, array_values($ports));
        $serverConfig->setMasterPidFile($config->get('application.logging.path').'/master.pid');

        return $serverConfig;
    }

    protected function addAccessLoggerConfig(Properties $config): void
    {
        $path = $config->get('application.logging.path');
        $config->mergeIfNotExists([
            'application' => [
                'logging' => [
                    'loggers' => [
                        'AccessLogLogger' => $this->createAccessLogger($path.'/access.log'),
                    ],
                    'logger' => [
                        AccessLog::class => 'AccessLogLogger',
                    ],
                ],
            ],
        ]);
    }

    public function createAccessLogger(string $logFileName): array
    {
        return [
            'handlers' => [
                [
                    'handler' => [
                        'class' => StreamHandler::class,
                        'constructor' => [
                            'stream' => $logFileName,
                        ],
                    ],
                    'formatter' => [
                        'class' => LineFormatter::class,
                        'constructor' => [
                            'format' => "%message% %context% %extra%\n",
                        ],
                    ],
                ],
            ],
            'processors' => [
                CoroutineIdProcessor::class,
            ],
        ];
    }

    protected function addEventListeners(Properties $config): void
    {
        $this->containerBuilder->defer(function (ContainerInterface $container) use ($config): void {
            $dispatcher = $container->get(EventDispatcherInterface::class);
            foreach ($config->get('application.listeners', []) as $key => $listener) {
                $eventListener = $container->get($listener);
                if ($eventListener instanceof EventListenerInterface) {
                    $dispatcher->addListener($eventListener->getSubscribedEvent(), $eventListener);
                } elseif ($eventListener instanceof EventSubscriberInterface) {
                    foreach ($eventListener->getSubscribedEvents() as $event) {
                        $dispatcher->addListener($event, $eventListener);
                    }
                } elseif (is_string($key)) {
                    $dispatcher->addListener($key, $eventListener);
                }
            }
            $dispatcher->addListener(StartEvent::class, $container->get(StartEventListener::class));
            $dispatcher->addListener(ManagerStartEvent::class, $container->get(ManagerStartEventListener::class));
            $dispatcher->addListener(WorkerStartEvent::class, $container->get(WorkerStartEventListener::class));
            $dispatcher->addListener(TaskEventListener::class, $container->get(TaskEventListener::class));
        });
    }
}
