<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\config;

use function DI\factory;
use kuiper\jsonrpc\server\JsonRpcServerFactory;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\swoole\Application;
use Psr\Http\Server\RequestHandlerInterface;

class JsonRpcHttpServerConfiguration extends AbstractJsonRpcServerConfiguration
{
    public function getDefinitions(): array
    {
        $this->addAccessLogConfig();

        return [
            RequestHandlerInterface::class => factory([JsonRpcServerFactory::class, 'createHttpRequestHandler']),
        ];
    }

    protected function addAccessLogConfig(): void
    {
        Application::getInstance()->getConfig()->mergeIfNotExists([
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
