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

namespace kuiper\jsonrpc\server;

use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\RpcServerResponseFactoryInterface;
use kuiper\rpc\server\RpcServerRpcRequestHandler;
use kuiper\rpc\server\Service;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use kuiper\serializer\NormalizerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonRpcServerFactory
{
    private ?RpcServerRequestFactoryInterface $rpcServerRequestFactory = null;

    private ?RpcRequestHandlerInterface $rpcRequestHandler = null;

    private ?InvalidRequestHandlerInterface $invalidRequestHandler = null;

    /**
     * @param array<string, Service>             $services
     * @param MiddlewareInterface[]              $middlewares
     * @param ServerRequestFactoryInterface      $httpRequestFactory
     * @param ResponseFactoryInterface           $httpResponseFactory
     * @param StreamFactoryInterface             $streamFactory
     * @param NormalizerInterface                $normalizer
     * @param ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory
     */
    public function __construct(
        private readonly array $services,
        private readonly array $middlewares,
        private readonly ServerRequestFactoryInterface $httpRequestFactory,
        private readonly ResponseFactoryInterface $httpResponseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly NormalizerInterface $normalizer,
        private readonly ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory
    ) {
    }

    public function getRpcResponseFactory(): RpcServerResponseFactoryInterface
    {
        return new JsonRpcServerResponseFactory($this->httpResponseFactory, $this->streamFactory);
    }

    /**
     * @return RpcServerRequestFactoryInterface|null
     */
    public function getRpcServerRequestFactory(): ?RpcServerRequestFactoryInterface
    {
        if (null === $this->rpcServerRequestFactory) {
            $rpcMethodFactory = new JsonRpcServerMethodFactory($this->services, $this->normalizer, $this->reflectionDocBlockFactory);
            $this->rpcServerRequestFactory = new JsonRpcServerRequestFactory($rpcMethodFactory);
        }

        return $this->rpcServerRequestFactory;
    }

    /**
     * @param RpcServerRequestFactoryInterface|null $rpcServerRequestFactory
     */
    public function setRpcServerRequestFactory(?RpcServerRequestFactoryInterface $rpcServerRequestFactory): void
    {
        $this->rpcServerRequestFactory = $rpcServerRequestFactory;
    }

    /**
     * @return RpcRequestHandlerInterface|null
     */
    public function getRpcRequestHandler(): ?RpcRequestHandlerInterface
    {
        if (null === $this->rpcRequestHandler) {
            $this->rpcRequestHandler = new RpcServerRpcRequestHandler($this->services, $this->getRpcResponseFactory(), $this->middlewares);
        }

        return $this->rpcRequestHandler;
    }

    /**
     * @param RpcRequestHandlerInterface|null $rpcRequestHandler
     */
    public function setRpcRequestHandler(?RpcRequestHandlerInterface $rpcRequestHandler): void
    {
        $this->rpcRequestHandler = $rpcRequestHandler;
    }

    /**
     * @return InvalidRequestHandlerInterface|null
     */
    public function getInvalidRequestHandler(): ?InvalidRequestHandlerInterface
    {
        if (null === $this->invalidRequestHandler) {
            $this->invalidRequestHandler = new ErrorHandler($this->httpResponseFactory, $this->streamFactory, new ExceptionNormalizer());
        }

        return $this->invalidRequestHandler;
    }

    /**
     * @param InvalidRequestHandlerInterface|null $invalidRequestHandler
     */
    public function setInvalidRequestHandler(?InvalidRequestHandlerInterface $invalidRequestHandler): void
    {
        $this->invalidRequestHandler = $invalidRequestHandler;
    }

    public static function createFromContainer(ContainerInterface $container): self
    {
        return new self(
            $container->get('jsonrpcServices'),
            $container->get('jsonrpcServerMiddlewares'),
            $container->get(ServerRequestFactoryInterface::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(NormalizerInterface::class),
            $container->get(ReflectionDocBlockFactoryInterface::class)
        );
    }

    public function createHttpRequestHandler(): RequestHandlerInterface
    {
        return new JsonRpcHttpRequestHandler(
            $this->getRpcServerRequestFactory(),
            $this->getRpcRequestHandler(),
            $this->getInvalidRequestHandler()
        );
    }

    public function createTcpRequestEventListener(): JsonRpcTcpReceiveEventListener
    {
        return new JsonRpcTcpReceiveEventListener(
            $this->httpRequestFactory,
            $this->getRpcServerRequestFactory(),
            $this->getRpcRequestHandler(),
            $this->getInvalidRequestHandler()
        );
    }
}
