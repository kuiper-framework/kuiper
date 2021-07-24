<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\config;

use kuiper\jsonrpc\JsonRpcProtocol;
use kuiper\jsonrpc\server\JsonRpcTcpReceiveEventListener;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\swoole\Application;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\event\ReceiveEvent;

class JsonRpcTcpServerConfiguration extends AbstractJsonRpcServerConfiguration
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
                'listeners' => [
                    ReceiveEvent::class => JsonRpcTcpReceiveEventListener::class,
                ],
                'swoole' => [
                    ServerSetting::OPEN_EOF_SPLIT => true,
                    ServerSetting::PACKAGE_EOF => JsonRpcProtocol::EOF,
                ],
            ],
        ]);

        return [
        ];
    }
}
