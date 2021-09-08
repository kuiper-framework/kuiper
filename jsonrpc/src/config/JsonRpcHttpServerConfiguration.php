<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\config;

use function DI\factory;
use kuiper\di\annotation\Bean;
use kuiper\jsonrpc\server\JsonRpcServerFactory;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\swoole\Application;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\ServerConfig;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonRpcHttpServerConfiguration extends AbstractJsonRpcServerConfiguration
{
    public function getDefinitions(): array
    {
        $this->addAccessLogConfig();

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

    protected function addAccessLogConfig(): void
    {
        Application::getInstance()->getConfig()->merge([
            'application' => [
                'logging' => [
                    'logger' => [
                        AccessLog::class => 'AccessLogLogger',
                    ],
                ],
                'jsonrpc' => [
                    'server' => [
                        'middleware' => [
                            AccessLog::class,
                        ],
                    ],
                ],
            ],
        ]);
    }
}
