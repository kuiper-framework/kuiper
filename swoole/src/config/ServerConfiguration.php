<?php

declare(strict_types=1);

namespace kuiper\swoole\config;


use kuiper\swoole\Application;
use kuiper\swoole\ServerCommand;
use Slim\App;
use function DI\autowire;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\Configuration;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\http\HttpMessageFactoryHolder;
use kuiper\swoole\http\SwooleRequestBridgeInterface;
use kuiper\swoole\http\SwooleResponseBridge;
use kuiper\swoole\http\SwooleResponseBridgeInterface;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\ServerConfig;
use kuiper\swoole\ServerFactory;
use kuiper\swoole\ServerPort;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class ServerConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        Application::getInstance()->getConfig()->mergeIfNotExists([
            'default_command' => ServerCommand::NAME,
            'commands' => [
                ServerCommand::NAME => ServerCommand::class
            ],
        ]);
        return [
            SwooleResponseBridgeInterface::class => autowire(SwooleResponseBridge::class),
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
        $serverFactory->enablePhpServer($config->getBool('application.server.enable-php-server'));
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
        $app = Application::getInstance();
        $app->getConfig()->get('application.swoole');

        $ports = [];
        foreach ($serverProperties->getAdapters() as $adapter) {
            $port = $adapter->getEndpoint()->getPort();
            if (isset($ports[$port])) {
                continue;
            }
            $ports[$port] = new ServerPort($adapter->getEndpoint()->getHost(), $port, $adapter->getServerType());
        }

        $settings = array_merge($serverProperties->getServerSettings(),
            );
        $serverConfig = new ServerConfig($serverProperties->getServerName(), $settings, array_values($ports));
        $serverConfig->setMasterPidFile($serverProperties->getDataPath().'/master.pid');

        return $serverConfig;
    }
}
