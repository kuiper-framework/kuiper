<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RpcRpcRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class JsonRpcRequest extends RpcRpcRequest implements JsonRpcRequestInterface
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

    public function __construct(RequestInterface $request, InvokingMethod $invokingMethod, StreamFactoryInterface $streamFactory, int $requestId)
    {
        parent::__construct($request, $invokingMethod);
        $this->requestId = $requestId;
        $this->streamFactory = $streamFactory;
    }

    public function getRequestId(): int
    {
        return $this->requestId;
    }

    protected function getJsonRpcMethod(): string
    {
        $method = $this->getInvokingMethod();

        return str_replace('\\', '.', ProxyGenerator::getInterfaceName($method->getTargetClass())).'.'.$method->getMethodName();
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): StreamInterface
    {
        if (null === $this->body) {
            $this->body = $this->streamFactory->createStream(json_encode([
                'id' => $this->requestId,
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'method' => $this->getJsonRpcMethod(),
                'params' => $this->getInvokingMethod()->getArguments(),
            ]));
        }

        return $this->body;
    }
}
