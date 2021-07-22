<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\rpc\HasRequestIdInterface;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;
use kuiper\rpc\server\ServerResponseFactoryInterface;
use kuiper\tars\core\MethodMetadataFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Webmozart\Assert\Assert;

class TarsServerResponseFactory implements ServerResponseFactoryInterface
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

    public function createResponse(RequestInterface $request): ResponseInterface
    {
        Assert::isInstanceOf($request, HasRequestIdInterface::class);
        /* @var TarsServerRpcRequest $request */
        $response = $this->httpResponseFactory->createResponse();

        $metadata = $this->methodMetadataFactory->create(
            $request->getInvokingMethod()->getTarget(),
            $request->getInvokingMethod()->getMethodName()
        );

        return new TarsServerRpcResponse($request, $response, $metadata, $this->streamFactory);
    }
}
