<?php

declare(strict_types=1);

namespace kuiper\swoole\config;

use DI\Annotation\Inject;
use function DI\autowire;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnClass;
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
use kuiper\swoole\listener\WorkerExitEventListener;
use kuiper\swoole\listener\WorkerStartEventListener;
use kuiper\swoole\monolog\CoroutineIdProcessor;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\ServerConfig;
use kuiper\swoole\ServerFactory;
use kuiper\swoole\ServerPort;
use kuiper\swoole\ServerStartCommand;
use kuiper\swoole\ServerStopCommand;
use kuiper\web\LineRequestLogFormatter;
use kuiper\web\RequestLogFormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @ConditionalOnClass(ConsoleApplication::class)
 */
class ServerConfiguration implements DefinitionConfiguration, Bootstrap
{
    use ContainerBuilderAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function getDefinitions(): array
    {
        $config = Application::getInstance()->getConfig();
        $basePath = Application::getInstance()->getBasePath();
        $config->mergeIfNotExists([
            'application' => [
                'name' => 'app',
                'base_path' => $basePath,
                'default_command' => 'start',
                'commands' => [
                    'start' => ServerStartCommand::class,
                    'stop' => ServerStopCommand::class,
                ],
                'logging' => [
                    'path' => $basePath.'/logs',
                ],
            ],
        ]);
        if (!$config->has('application.server.ports')) {
            $config->set('application.server.ports', ['8000' => ServerType::HTTP]);
        }

        return [
            SwooleResponseBridgeInterface::class => autowire(SwooleResponseBridge::class),
            RequestLogFormatterInterface::class => autowire(LineRequestLogFormatter::class),
        ];
    }

    public function boot(ContainerInterface $container): void
    {
        $logger = $container->get(LoggerInterface::class);
        $dispatcher = $container->get(EventDispatcherInterface::class);
        $config = $container->get(PropertyResolverInterface::class);
        $events = [];
        $addListener = static function ($eventName, $listener) use ($container, $dispatcher, $logger, &$events): void {
            $eventListener = is_string($listener) ? $container->get($listener) : $listener;
            if ($eventListener instanceof EventListenerInterface) {
                $event = $eventListener->getSubscribedEvent();
                $dispatcher->addListener($event, $eventListener);
                $events[$event] = true;
                $logger->info(static::TAG."add event listener {$listener} for {$event}");
            } elseif ($eventListener instanceof EventSubscriberInterface) {
                foreach ($eventListener->getSubscribedEvents() as $event) {
                    $dispatcher->addListener($event, $eventListener);
                    $events[$event] = true;
                    $logger->info(static::TAG."add event listener {$listener} for {$event}");
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
        $addListener(null, WorkerExitEventListener::class);
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
        ], $config->get('application.swoole') ?? []);

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
                array_merge($settings, $portConfig)
            );
        }

        $serverConfig = new ServerConfig($name, $ports);
        $serverConfig->setMasterPidFile($config->get('application.logging.path').'/master.pid');

        return $serverConfig;
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
