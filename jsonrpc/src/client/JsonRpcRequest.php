<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\jsonrpc\JsonRpcProtocol;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class JsonRpcRequest extends RpcRequest implements JsonRpcRequestInterface
{
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var StreamInterface
     */
    private $body;

    /**
     * @var int
     */
    private $requestId;

    public function __construct(RequestInterface $request, RpcMethodInterface $rpcMethod, StreamFactoryInterface $streamFactory, int $requestId)
    {
        parent::__construct($request, $rpcMethod);
        $this->requestId = $requestId;
        $this->streamFactory = $streamFactory;
    }

    public function getRequestId(): int
    {
        return $this->requestId;
    }

    protected function getJsonRpcMethod(): string
    {
        return $this->getRpcMethod()->getServiceName().'.'.$this->getRpcMethod()->getMethodName();
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): StreamInterface
    {
        if (null === $this->body) {
            $this->body = $this->streamFactory->createStream(JsonRpcProtocol::encode([
                'id' => $this->requestId,
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'method' => $this->getJsonRpcMethod(),
                'params' => $this->getRpcMethod()->getArguments(),
            ]));
        }

        return $this->body;
    }
}
