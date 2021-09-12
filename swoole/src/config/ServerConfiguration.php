<?php

declare(strict_types=1);

namespace kuiper\swoole\config;

use function DI\autowire;
use kuiper\di\annotation\Bean;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\PropertyResolverInterface;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\Application;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\http\HttpMessageFactoryHolder;
use kuiper\swoole\http\SwooleRequestBridgeInterface;
use kuiper\swoole\http\SwooleResponseBridge;
use kuiper\swoole\http\SwooleResponseBridgeInterface;
use kuiper\swoole\listener\ManagerStartEventListener;
use kuiper\swoole\listener\StartEventListener;
use kuiper\swoole\listener\TaskEventListener;
use kuiper\swoole\listener\WorkerExitEventListener;
use kuiper\swoole\listener\WorkerStartEventListener;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\ServerConfig;
use kuiper\swoole\ServerFactory;
use kuiper\swoole\ServerPort;
use kuiper\swoole\ServerStartCommand;
use kuiper\swoole\ServerStopCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ServerConfiguration implements DefinitionConfiguration
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
        $config->merge([
            'application' => [
                'listeners' => [
                    StartEventListener::class,
                    ManagerStartEventListener::class,
                    WorkerStartEventListener::class,
                    WorkerExitEventListener::class,
                    TaskEventListener::class,
                ],
            ],
        ]);
        if (!$config->has('application.server.ports')) {
            $config->set('application.server.ports', ['8000' => ServerType::HTTP]);
        }

        return [
            SwooleResponseBridgeInterface::class => autowire(SwooleResponseBridge::class),
        ];
    }

    /**
     * @Bean
     */
    public function consoleApplication(PropertyResolverInterface $config): ConsoleApplication
    {
        return new ConsoleApplication($config->get('application.name', 'app'));
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
     */
    public function serverConfig(): ServerConfig
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

        $serverConfig = new ServerConfig($config->getString('application.name', 'app'), $ports);
        $serverConfig->setMasterPidFile($config->get('application.logging.path').'/master.pid');

        return $serverConfig;
    }
}
