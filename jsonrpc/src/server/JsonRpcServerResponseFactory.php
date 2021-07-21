<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\jsonrpc\client\JsonRpcRequest;
use kuiper\rpc\HasRequestIdInterface;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;
use kuiper\rpc\RpcResponse;
use kuiper\rpc\server\ServerResponseFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Webmozart\Assert\Assert;

class JsonRpcServerResponseFactory implements ServerResponseFactoryInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $httpResponseFactory;

    /**
     * JsonRpcServerResponseFactory constructor.
     */
    public function __construct(ResponseFactoryInterface $httpResponseFactory)
    {
        $this->httpResponseFactory = $httpResponseFactory;
    }

    public function createResponse(RequestInterface $request): ResponseInterface
    {
        Assert::isInstanceOf($request, HasRequestIdInterface::class);
        $response = $this->httpResponseFactory->createResponse();
        $response->getBody()->write(json_encode([
            'jsonrpc' => JsonRpcRequest::JSONRPC_VERSION,
            'id' => $request->getRequestId(),
            'result' => $request->getInvokingMethod()->getResult(),
        ]));

        return new RpcResponse($request, $response);
    }
}
