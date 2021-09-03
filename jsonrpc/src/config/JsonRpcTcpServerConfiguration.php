<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\config;

use DI\Annotation\Inject;
use kuiper\di\annotation\Bean;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\jsonrpc\server\JsonRpcTcpReceiveEventListener;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use kuiper\swoole\Application;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\event\ReceiveEvent;
use Psr\Http\Message\RequestFactoryInterface;

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

    /**
     * @Bean
     * @Inject({
     *     "serverRequestFactory": "jsonrpcServerRequestFactory",
     *     "requestHandler": "jsonrpcRequestHandler",
     * })
     */
    public function jsonRpcTcpReceiveEventListener(
        RequestFactoryInterface $httpRequestFactory,
        RpcServerRequestFactoryInterface $serverRequestFactory,
        RpcRequestHandlerInterface $requestHandler,
        ExceptionNormalizer $exceptionNormalizer,
        LoggerFactoryInterface $loggerFactory
    ): JsonRpcTcpReceiveEventListener {
        $listener = new JsonRpcTcpReceiveEventListener($httpRequestFactory, $serverRequestFactory, $requestHandler, $exceptionNormalizer);
        $listener->setLogger($loggerFactory->create(JsonRpcTcpReceiveEventListener::class));

        return $listener;
    }
}
