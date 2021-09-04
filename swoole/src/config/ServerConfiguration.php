<?php

declare(strict_types=1);

namespace kuiper\swoole\config;

use DI\Annotation\Inject;
use function DI\autowire;
use kuiper\di\annotation\Bean;
use kuiper\di\Bootstrap;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\event\annotation\EventListener;
use kuiper\event\EventListenerInterface;
use kuiper\event\EventSubscriberInterface;
use kuiper\helper\PropertyResolverInterface;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\Application;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\event\RequestEvent;
use kuiper\swoole\http\HttpMessageFactoryHolder;
use kuiper\swoole\http\SwooleRequestBridgeInterface;
use kuiper\swoole\http\SwooleResponseBridge;
use kuiper\swoole\http\SwooleResponseBridgeInterface;
use kuiper\swoole\listener\HttpRequestEventListener;
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
use kuiper\swoole\task\Queue;
use kuiper\swoole\task\QueueInterface;
use kuiper\web\LineRequestLogFormatter;
use kuiper\web\RequestLogFormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ServerConfiguration implements DefinitionConfiguration, Bootstrap
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
                    'access_log_file' => '{application.logging.path}/access.log',
                ],
            ],
        ]);
        $this->addAccessLoggerConfig();
        if (!$config->has('application.server.ports')) {
            $config->set('application.server.ports', ['8000' => ServerType::HTTP]);
        }

        return [
            QueueInterface::class => autowire(Queue::class),
            SwooleResponseBridgeInterface::class => autowire(SwooleResponseBridge::class),
            RequestLogFormatterInterface::class => autowire(LineRequestLogFormatter::class),
        ];
    }

    public function boot(ContainerInterface $container): void
    {
        $dispatcher = $container->get(EventDispatcherInterface::class);
        $config = $container->get(PropertyResolverInterface::class);
        $events = [];
        $addListener = static function ($eventName, $listener) use ($container, $dispatcher, &$events): void {
            $eventListener = $container->get($listener);
            if ($eventListener instanceof EventListenerInterface) {
                $dispatcher->addListener($eventListener->getSubscribedEvent(), $eventListener);
                $events[$eventListener->getSubscribedEvent()] = true;
            } elseif ($eventListener instanceof EventSubscriberInterface) {
                foreach ($eventListener->getSubscribedEvents() as $event) {
                    $dispatcher->addListener($event, $eventListener);
                    $events[$event] = true;
                }
            } elseif (is_string($eventName)) {
                $dispatcher->addListener($eventName, $eventListener);
                $events[$eventName] = true;
            }
        };
        foreach ($config->get('application.listeners', []) as $key => $listener) {
            $addListener($key, $listener);
        }
        $addListener(null, StartEventListener::class);
        $addListener(null, ManagerStartEventListener::class);
        $addListener(null, WorkerStartEventListener::class);
        $addListener(null, TaskEventListener::class);
        /** @var EventListener $annotation */
        foreach (ComponentCollection::getAnnotations(EventListener::class) as $annotation) {
            $addListener($annotation->value, $annotation->getComponentId());
        }
        $serverConfig = $container->get(ServerConfig::class);
        if (!isset($events[RequestEvent::class]) && $serverConfig->getPort()->isHttpProtocol()) {
            $addListener(null, HttpRequestEventListener::class);
        }
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

    protected function addAccessLoggerConfig(): void
    {
        $config = Application::getInstance()->getConfig();
        $config->mergeIfNotExists([
            'application' => [
                'logging' => [
                    'loggers' => [
                        'AccessLogLogger' => self::createAccessLogger($config->get('application.logging.access_log_file')),
                    ],
                ],
            ],
        ]);
    }

    public static function createAccessLogger(string $logFileName): array
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
}
