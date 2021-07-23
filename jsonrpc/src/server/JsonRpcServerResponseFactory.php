<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\HasRequestIdInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\rpc\RpcRpcResponse;
use kuiper\rpc\server\RpcServerResponseFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Webmozart\Assert\Assert;

class JsonRpcServerResponseFactory implements RpcServerResponseFactoryInterface
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

    /**
     * {@inheritDoc}
     */
    public function createResponse(RpcRequestInterface $request): RpcResponseInterface
    {
        Assert::isInstanceOf($request, HasRequestIdInterface::class);
        $response = $this->httpResponseFactory->createResponse();
        /* @var JsonRpcRequestInterface $request */
        $response->getBody()->write(json_encode([
            'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
            'id' => $request->getRequestId(),
            'result' => $this->getResult($request),
        ]));

        return new RpcRpcResponse($request, $response->withHeader('content-type', 'application/json'));
    }

    /**
     * @return mixed
     */
    protected function getResult(RpcRequestInterface $request)
    {
        return $request->getInvokingMethod()->getResult();
    }
}
