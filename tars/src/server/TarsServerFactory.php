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

use kuiper\annotations\AnnotationReaderInterface;
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
     * @var AnnotationReaderInterface
     */
    private $annotationReader;

    /**
     * @var RequestFactoryInterface
     */
    private $httpRequestFactory;
    /**
     * @var ResponseFactoryInterface
     */
    private $httpResponseFactory;
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var ServerProperties
     */
    private $serverProperties;

    /**
     * @var LoggerFactoryInterface
     */
    private $loggerFactory;

    /**
     * @var Service[]
     */
    private $services;
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;

    public function __construct(
        AnnotationReaderInterface $annotationReader,
        RequestFactoryInterface $httpRequestFactory,
        ResponseFactoryInterface $httpResponseFactory,
        StreamFactoryInterface $streamFactory,
        ServerProperties $serverProperties,
        LoggerFactoryInterface $loggerFactory,
        array $services,
        array $middlewares
    ) {
        $this->annotationReader = $annotationReader;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->httpResponseFactory = $httpResponseFactory;
        $this->streamFactory = $streamFactory;
        $this->serverProperties = $serverProperties;
        $this->loggerFactory = $loggerFactory;
        $this->services = $services;
        $this->middlewares = $middlewares;
    }

    public function createRpcMethodFactory(): RpcMethodFactoryInterface
    {
        return new TarsServerMethodFactory($this->serverProperties, $this->services, $this->annotationReader);
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
            $container->get(AnnotationReaderInterface::class),
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
