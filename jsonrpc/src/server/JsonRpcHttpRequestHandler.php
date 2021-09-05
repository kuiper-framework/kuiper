<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\jsonrpc\exception\JsonRpcRequestException;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
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
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var ErrorResponseHandlerInterface
     */
    private $errorResponseHandler;

    /**
     * JsonRpcHttpRequestHandler constructor.
     */
    public function __construct(
        RpcServerRequestFactoryInterface $requestFactory,
        RpcRequestHandlerInterface $requestHandler,
        ResponseFactoryInterface $responseFactory,
        ErrorResponseHandlerInterface $errorResponseHandler
    ) {
        $this->requestFactory = $requestFactory;
        $this->requestHandler = $requestHandler;
        $this->responseFactory = $responseFactory;
        $this->errorResponseHandler = $errorResponseHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            /** @var JsonRpcRequestInterface $rpcRequest */
            $rpcRequest = $this->requestFactory->createRequest($request);
        } catch (JsonRpcRequestException $e) {
            return $this->createResponse(400, $this->errorResponseHandler->handle($e));
        }
        try {
            return $this->requestHandler->handle($rpcRequest);
        } catch (\Exception $e) {
            return $this->createResponse(500, $this->errorResponseHandler->handle($e, $rpcRequest));
        }
    }

    private function createResponse(int $statusCode, string $body): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($statusCode)
            ->withHeader('content-type', 'application/json');
        $response->getBody()->write($body);

        return $response;
    }
}
