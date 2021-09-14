<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\rpc\ErrorHandlerInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\RpcServerResponseFactoryInterface;
use kuiper\rpc\server\RpcServerRpcRequestHandler;
use kuiper\rpc\server\Service;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use kuiper\serializer\NormalizerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonRpcServerFactory
{
    /**
     * @var Service[]
     */
    private $services;

    /**
     * @var array
     */
    private $middlewares;

    /**
     * @var bool
     */
    private $outParams;

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
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var ReflectionDocBlockFactoryInterface
     */
    private $reflectionDocBlockFactory;

    /**
     * @var RpcServerRequestFactoryInterface|null
     */
    private $rpcServerRequestFactory;

    /**
     * @var RpcRequestHandlerInterface|null
     */
    private $rpcRequestHandler;

    /**
     * @var InvalidRequestHandlerInterface|null
     */
    private $invalidRequestHandler;

    /**
     * @var ErrorHandlerInterface|null
     */
    private $errorHandler;

    /**
     * JsonRpcServerFactory constructor.
     *
     * @param array                    $services
     * @param array                    $middlewares
     * @param bool                     $outParams
     * @param ResponseFactoryInterface $httpResponseFactory
     * @param StreamFactoryInterface   $streamFactory
     * @param NormalizerInterface      $normalizer
     */
    public function __construct(
        array $services,
        array $middlewares,
        bool $outParams,
        RequestFactoryInterface $httpRequestFactory,
        ResponseFactoryInterface $httpResponseFactory,
        StreamFactoryInterface $streamFactory,
        NormalizerInterface $normalizer,
        ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory
    ) {
        $this->services = $services;
        $this->middlewares = $middlewares;
        $this->outParams = $outParams;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->httpResponseFactory = $httpResponseFactory;
        $this->streamFactory = $streamFactory;
        $this->normalizer = $normalizer;
        $this->reflectionDocBlockFactory = $reflectionDocBlockFactory;
    }

    public function getRpcResponseFactory(): RpcServerResponseFactoryInterface
    {
        $responseClass = $this->outParams ? OutParamJsonRpcServerResponse::class : JsonRpcServerResponse::class;

        return new JsonRpcServerResponseFactory($this->httpResponseFactory, $this->streamFactory, $responseClass);
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
            $this->rpcRequestHandler = new RpcServerRpcRequestHandler($this->services, $this->getRpcResponseFactory(), $this->getErrorHandler(), $this->middlewares);
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

    /**
     * @return ErrorHandlerInterface|null
     */
    public function getErrorHandler(): ?ErrorHandlerInterface
    {
        if (null === $this->errorHandler) {
            $this->errorHandler = new ErrorHandler($this->httpResponseFactory, $this->streamFactory, new ExceptionNormalizer());
        }

        return $this->errorHandler;
    }

    /**
     * @param ErrorHandlerInterface|null $errorHandler
     */
    public function setErrorHandler(?ErrorHandlerInterface $errorHandler): void
    {
        $this->errorHandler = $errorHandler;
    }

    public static function createFromContainer(ContainerInterface $container): self
    {
        return new self(
            $container->get('jsonrpcServices'),
            $container->get('jsonrpcServerMiddlewares'),
            (bool) $container->get('application.jsonrpc.server.out_params'),
            $container->get(RequestFactoryInterface::class),
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
            $this->getInvalidRequestHandler(),
            $this->getErrorHandler()
        );
    }

    public function createTcpRequestEventListener(): JsonRpcTcpReceiveEventListener
    {
        return new JsonRpcTcpReceiveEventListener(
            $this->httpRequestFactory,
            $this->getRpcServerRequestFactory(),
            $this->getRpcRequestHandler(),
            $this->getInvalidRequestHandler(),
            $this->getErrorHandler()
        );
    }
}
