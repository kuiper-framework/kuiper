<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\config;

use function DI\autowire;
use kuiper\jsonrpc\server\JsonRpcHttpRequestHandler;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\swoole\Application;
use Psr\Http\Server\RequestHandlerInterface;

class JsonRpcHttpServerConfiguration extends AbstractJsonRpcServerConfiguration
{
    public function getDefinitions(): array
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

        return [
            RequestHandlerInterface::class => autowire(JsonRpcHttpRequestHandler::class),
        ];
    }
}
