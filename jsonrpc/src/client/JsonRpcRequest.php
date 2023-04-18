<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class JsonRpcRequest extends RpcRequest implements JsonRpcRequestInterface
{
    private ?StreamInterface $body = null;

    public function __construct(
        RequestInterface $request,
        RpcMethodInterface $rpcMethod,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly int $requestId,
        private readonly string $version
    ) {
        parent::__construct($request, $rpcMethod);
    }

    public function getRequestId(): int
    {
        return $this->requestId;
    }

    public function getJsonRpcVersion(): string
    {
        return $this->version;
    }

    protected function getJsonRpcMethod(): string
    {
        return $this->getRpcMethod()->getServiceLocator()->getName().'.'.$this->getRpcMethod()->getMethodName();
    }

    public function withBody(StreamInterface $body)
    {
        $copy = clone $this;
        $copy->body = $body;

        return $copy;
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): StreamInterface
    {
        if (null === $this->body) {
            $this->body = $this->streamFactory->createStream(JsonRpcProtocol::encode([
                JsonRpcProtocol::ID => $this->requestId,
                JsonRpcProtocol::JSONRPC => JsonRpcProtocol::VERSION,
                JsonRpcProtocol::METHOD => $this->getJsonRpcMethod(),
                JsonRpcProtocol::PARAMS => $this->getRpcMethod()->getArguments(),
                JsonRpcProtocol::EXTENDED => JsonRpcProtocol::EXTENDED_VERSION,
            ]));
        }

        return $this->body;
    }
}
