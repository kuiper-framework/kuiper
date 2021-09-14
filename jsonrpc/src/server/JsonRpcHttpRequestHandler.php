<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\jsonrpc\exception\JsonRpcRequestException;
use kuiper\rpc\ErrorHandlerInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\ServerRequestHolder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonRpcHttpRequestHandler implements RequestHandlerInterface
{
    /**
     * @var RpcServerRequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var RpcRequestHandlerInterface
     */
    private $requestHandler;

    /**
     * @var InvalidRequestHandlerInterface
     */
    private $invalidRequestHandler;
    /**
     * @var ErrorHandlerInterface
     */
    private $errorHandler;

    /**
     * JsonRpcHttpRequestHandler constructor.
     */
    public function __construct(
        RpcServerRequestFactoryInterface $requestFactory,
        RpcRequestHandlerInterface $requestHandler,
        InvalidRequestHandlerInterface $invalidRequestHandler,
        ErrorHandlerInterface $errorHandler
    ) {
        $this->requestFactory = $requestFactory;
        $this->requestHandler = $requestHandler;
        $this->invalidRequestHandler = $invalidRequestHandler;
        $this->errorHandler = $errorHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            /** @var JsonRpcRequestInterface $rpcRequest */
            $rpcRequest = $this->requestFactory->createRequest($request);
        } catch (JsonRpcRequestException $e) {
            return $this->invalidRequestHandler->handleInvalidRequest($request, $e);
        }
        try {
            ServerRequestHolder::setRequest($rpcRequest);

            return $this->requestHandler->handle($rpcRequest);
        } catch (\Exception $e) {
            return $this->errorHandler->handle($rpcRequest, $e);
        }
    }
}
