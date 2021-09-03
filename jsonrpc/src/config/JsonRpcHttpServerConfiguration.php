<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\config;

use DI\Annotation\Inject;
use kuiper\di\annotation\Bean;
use kuiper\jsonrpc\server\JsonRpcHttpRequestHandler;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use kuiper\swoole\Application;
use Psr\Http\Message\ResponseFactoryInterface;
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
        ];
    }

    /**
     * @Bean
     * @Inject({
     *     "requestFactory": "jsonrpcServerRequestFactory",
     *     "requestHandler": "jsonrpcRequestHandler",
     * })
     */
    public function jsonrpcHttpRequestHandler(
        RpcServerRequestFactoryInterface $requestFactory,
        RpcRequestHandlerInterface $requestHandler,
        ResponseFactoryInterface $responseFactory,
        ExceptionNormalizer $exceptionNormalizer): RequestHandlerInterface
    {
        return new JsonRpcHttpRequestHandler($requestFactory, $requestHandler, $responseFactory, $exceptionNormalizer);
    }
}
