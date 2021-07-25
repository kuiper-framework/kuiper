<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\rpc\server\RpcServerResponseFactoryInterface;
use kuiper\tars\core\TarsRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Webmozart\Assert\Assert;

class TarsServerResponseFactory implements RpcServerResponseFactoryInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $httpResponseFactory;
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * JsonRpcServerResponseFactory constructor.
     */
    public function __construct(ResponseFactoryInterface $httpResponseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->httpResponseFactory = $httpResponseFactory;
        $this->streamFactory = $streamFactory;
    }

    public function createResponse(RpcRequestInterface $request): RpcResponseInterface
    {
        Assert::isInstanceOf($request, TarsRequestInterface::class);
        $response = $this->httpResponseFactory->createResponse();

        /** @var TarsRequestInterface $request */
        return new TarsServerRpcResponse($request, $response, $this->streamFactory);
    }
}
