<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\config;

use function DI\factory;
use kuiper\di\annotation\Bean;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\jsonrpc\server\JsonRpcServerFactory;
use kuiper\jsonrpc\server\JsonRpcTcpReceiveEventListener;
use kuiper\swoole\Application;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\ServerConfig;
use Psr\Container\ContainerInterface;

class JsonRpcTcpServerConfiguration extends AbstractJsonRpcServerConfiguration
{
    public function getDefinitions(): array
    {
        Application::getInstance()->getConfig()->merge([
            'application' => [
                'listeners' => [
                    JsonRpcTcpReceiveEventListener::class,
                ],
                'swoole' => [
                    ServerSetting::OPEN_EOF_SPLIT => true,
                    ServerSetting::PACKAGE_EOF => JsonRpcProtocol::EOF,
                ],
            ],
        ]);

        return array_merge(parent::getDefinitions(), [
            JsonRpcTcpReceiveEventListener::class => factory([JsonRpcServerFactory::class, 'createTcpRequestEventListener']),
        ]);
    }

    /**
     * @Bean("jsonrpcServices")
     */
    public function jsonrpcServices(ContainerInterface $container, ServerConfig $serverConfig): array
    {
        return $this->getJsonrpcServices($container, $serverConfig, ServerType::TCP, (int) ($container->get('application.jsonrpc.server.weight') ?? 0));
    }
}
