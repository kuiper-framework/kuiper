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

    /**
     * @var string
     */
    private $version;

    public function __construct(RequestInterface $request, RpcMethodInterface $rpcMethod, StreamFactoryInterface $streamFactory, int $requestId, string $version)
    {
        parent::__construct($request, $rpcMethod);
        $this->requestId = $requestId;
        $this->streamFactory = $streamFactory;
        $this->version = $version;
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
        return (($serviceName = $this->getRpcMethod()->getServiceLocator()->getName()) !== '' ? $serviceName.'.' : '').$this->getRpcMethod()->getMethodName();
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
                'id' => $this->requestId,
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'method' => $this->getJsonRpcMethod(),
                'params' => $this->getRpcMethod()->getArguments(),
            ]));
        }

        return $this->body;
    }
}
