<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\RpcServerResponseFactoryInterface;
use kuiper\rpc\server\RpcServerRpcRequestHandler;
use kuiper\rpc\server\ServiceObject;
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
     * @var ServiceObject[]
     */
    private $services;

    /**
     * @var array
     */
    private $middlewares;

    /**
     * @var bool
     */
    private $enableOutParam;

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
     * @var ErrorResponseHandlerInterface|null
     */
    private $errorResponseHandler;

    /**
     * JsonRpcServerFactory constructor.
     *
     * @param array                    $services
     * @param array                    $middlewares
     * @param bool                     $enableOutParam
     * @param ResponseFactoryInterface $httpResponseFactory
     * @param StreamFactoryInterface   $streamFactory
     * @param NormalizerInterface      $normalizer
     */
    public function __construct(
        array $services,
        array $middlewares,
        bool $enableOutParam,
        RequestFactoryInterface $httpRequestFactory,
        ResponseFactoryInterface $httpResponseFactory,
        StreamFactoryInterface $streamFactory,
        NormalizerInterface $normalizer,
        ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory
    ) {
        $this->services = $services;
        $this->middlewares = $middlewares;
        $this->enableOutParam = $enableOutParam;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->httpResponseFactory = $httpResponseFactory;
        $this->streamFactory = $streamFactory;
        $this->normalizer = $normalizer;
        $this->reflectionDocBlockFactory = $reflectionDocBlockFactory;
    }

    public function getRpcResponseFactory(): RpcServerResponseFactoryInterface
    {
        $responseClass = $this->enableOutParam ? OutParamJsonRpcServerResponse::class : JsonRpcServerResponse::class;

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
     * @return ErrorResponseHandlerInterface|null
     */
    public function getErrorResponseHandler(): ?ErrorResponseHandlerInterface
    {
        if (null === $this->errorResponseHandler) {
            $this->errorResponseHandler = new ErrorResponseHandler(new ExceptionNormalizer());
        }

        return $this->errorResponseHandler;
    }

    /**
     * @param ErrorResponseHandlerInterface|null $errorResponseHandler
     */
    public function setErrorResponseHandler(?ErrorResponseHandlerInterface $errorResponseHandler): void
    {
        $this->errorResponseHandler = $errorResponseHandler;
    }

    public static function createFromContainer(ContainerInterface $container): self
    {
        return new self(
            $container->get('jsonrpcServices'),
            $container->get('jsonrpcServerMiddlewares'),
            (bool) $container->get('application.jsonrpc.server.enable_out_param'),
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
            $this->httpResponseFactory,
            $this->getErrorResponseHandler()
        );
    }

    public function createTcpRequestEventListener(): JsonRpcTcpReceiveEventListener
    {
        return new JsonRpcTcpReceiveEventListener(
            $this->httpRequestFactory,
            $this->getRpcServerRequestFactory(),
            $this->getRpcRequestHandler(),
            $this->getErrorResponseHandler()
        );
    }
}
