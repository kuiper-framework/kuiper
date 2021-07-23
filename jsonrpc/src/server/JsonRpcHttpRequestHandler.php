<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\jsonrpc\exception\JsonRpcRequestException;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\serializer\normalizer\ExceptionNormalizer;
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
     * @var ExceptionNormalizer
     */
    private $exceptionNormalizer;

    /**
     * JsonRpcHttpRequestHandler constructor.
     */
    public function __construct(RpcServerRequestFactoryInterface $requestFactory, RpcRequestHandlerInterface $requestHandler, ResponseFactoryInterface $responseFactory, ExceptionNormalizer $exceptionNormalizer)
    {
        $this->requestFactory = $requestFactory;
        $this->requestHandler = $requestHandler;
        $this->responseFactory = $responseFactory;
        $this->exceptionNormalizer = $exceptionNormalizer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            /** @var JsonRpcRequestInterface $rpcRequest */
            $rpcRequest = $this->requestFactory->createRequest($request);
        } catch (JsonRpcRequestException $e) {
            return $this->createRequestErrorResponse($e);
        }
        try {
            return $this->requestHandler->handle($rpcRequest);
        } catch (\Exception $e) {
            return $this->createErrorResponse($rpcRequest, $e);
        }
    }

    private function createRequestErrorResponse(JsonRpcRequestException $e): ResponseInterface
    {
        return $this->createResponse(400, [
            'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
            'id' => $e->getRequestId(),
            'error' => [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ],
        ]);
    }

    private function createErrorResponse(JsonRpcRequestInterface $rpcRequest, \Exception $e): ResponseInterface
    {
        return $this->createResponse(500, [
            'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
            'id' => $rpcRequest->getRequestId(),
            'error' => [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'data' => $this->exceptionNormalizer->normalize($e),
            ],
        ]);
    }

    private function createResponse(int $statusCode, array $body): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($statusCode)
            ->withHeader('content-type', 'application/json');
        $response->getBody()->write(json_encode($body));

        return $response;
    }
}
