<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\logger\LoggerFactoryInterface;
use kuiper\rpc\ErrorHandlerInterface;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\RpcServerResponseFactoryInterface;
use kuiper\rpc\server\RpcServerRpcRequestHandler;
use kuiper\rpc\server\Service;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class TarsServerFactory
{
    /**
     * @param RequestFactoryInterface  $httpRequestFactory
     * @param ResponseFactoryInterface $httpResponseFactory
     * @param StreamFactoryInterface   $streamFactory
     * @param ServerProperties         $serverProperties
     * @param LoggerFactoryInterface   $loggerFactory
     * @param Service[]                $services
     * @param MiddlewareInterface[]    $middlewares
     */
    public function __construct(
        private readonly RequestFactoryInterface $httpRequestFactory,
        private readonly ResponseFactoryInterface $httpResponseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ServerProperties $serverProperties,
        private readonly LoggerFactoryInterface $loggerFactory,
        private readonly array $services,
        private readonly array $middlewares
    ) {
    }

    public function createRpcMethodFactory(): RpcMethodFactoryInterface
    {
        return new TarsServerMethodFactory($this->serverProperties->getServerName(), $this->services);
    }

    public function createServerResponseFactory(): RpcServerResponseFactoryInterface
    {
        return new TarsServerResponseFactory($this->httpResponseFactory, $this->streamFactory);
    }

    public function createServerRequestFactory(): RpcServerRequestFactoryInterface
    {
        $tarsServerRequestFactory = new TarsServerRequestFactory($this->createRpcMethodFactory(), $this->services);
        $tarsServerRequestFactory->setLogger($this->loggerFactory->create(TarsServerRequestFactory::class));

        return $tarsServerRequestFactory;
    }

    public function createErrorHandler(): ErrorHandlerInterface
    {
        $errorHandler = new ErrorHandler($this->httpResponseFactory);
        $errorHandler->setLogger($this->loggerFactory->create(ErrorHandler::class));

        return $errorHandler;
    }

    public function getRequestHandler(): RpcRequestHandlerInterface
    {
        return new RpcServerRpcRequestHandler($this->services, $this->createServerResponseFactory(), $this->createErrorHandler(), $this->middlewares);
    }

    public static function createFromContainer(ContainerInterface $container): self
    {
        return new self(
            $container->get(RequestFactoryInterface::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(ServerProperties::class),
            $container->get(LoggerFactoryInterface::class),
            $container->get('tarsServices'),
            $container->get('tarsServerMiddlewares')
        );
    }

    public function createTcpReceiveEventListener(): TarsTcpReceiveEventListener
    {
        return new TarsTcpReceiveEventListener(
            $this->httpRequestFactory,
            $this->createServerRequestFactory(),
            $this->getRequestHandler()
        );
    }
}
