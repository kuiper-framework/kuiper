<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\rpc\server\RpcServerResponseFactoryInterface;
use kuiper\tars\core\MethodMetadataFactoryInterface;
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
     * @var MethodMetadataFactoryInterface
     */
    private $methodMetadataFactory;
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * JsonRpcServerResponseFactory constructor.
     */
    public function __construct(ResponseFactoryInterface $httpResponseFactory, MethodMetadataFactoryInterface $methodMetadataFactory, StreamFactoryInterface $streamFactory)
    {
        $this->httpResponseFactory = $httpResponseFactory;
        $this->methodMetadataFactory = $methodMetadataFactory;
        $this->streamFactory = $streamFactory;
    }

    public function createResponse(RpcRequestInterface $request): RpcResponseInterface
    {
        Assert::isInstanceOf($request, TarsRequestInterface::class);
        $response = $this->httpResponseFactory->createResponse();

        $metadata = $this->methodMetadataFactory->create(
            $request->getInvokingMethod()->getTarget(),
            $request->getInvokingMethod()->getMethodName()
        );

        /* @var TarsRequestInterface $request */
        return new TarsServerRpcResponse($request, $response, $metadata, $this->streamFactory);
    }
}
