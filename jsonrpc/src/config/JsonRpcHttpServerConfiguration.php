<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\config;

use function DI\factory;
use kuiper\di\annotation\Bean;
use kuiper\jsonrpc\server\JsonRpcServerFactory;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\ServerConfig;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonRpcHttpServerConfiguration extends AbstractJsonRpcServerConfiguration
{
    public function getDefinitions(): array
    {
        return array_merge(parent::getDefinitions(), [
            RequestHandlerInterface::class => factory([JsonRpcServerFactory::class, 'createHttpRequestHandler']),
        ]);
    }

    /**
     * @Bean("jsonrpcServices")
     */
    public function jsonrpcServices(ContainerInterface $container, ServerConfig $serverConfig): array
    {
        return $this->getJsonrpcServices($container, $serverConfig, ServerType::HTTP, (int) ($container->get('application.jsonrpc.server.weight') ?? 0));
    }
}
