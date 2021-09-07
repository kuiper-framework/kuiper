<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\client\RpcRequestFactoryInterface;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcRequestInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class JsonRpcRequestFactory implements RpcRequestFactoryInterface
{
    /**
     * @var RequestFactoryInterface
     */
    private $httpRequestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;
    /**
     * @var RpcMethodFactoryInterface
     */
    private $rpcMethodFactory;

    /**
     * @var string
     */
    private $baseUri;

    /**
     * @var int
     */
    private $id;

    public function __construct(RequestFactoryInterface $httpRequestFactory, StreamFactoryInterface $streamFactory, RpcMethodFactoryInterface $rpcMethodFactory, string $baseUri = '/', ?int $id = null)
    {
        $this->httpRequestFactory = $httpRequestFactory;
        $this->streamFactory = $streamFactory;
        $this->rpcMethodFactory = $rpcMethodFactory;
        $this->baseUri = $baseUri;
        $this->id = $id ?? random_int(0, 1 << 20);
    }

    public function createRequest(object $proxy, string $method, array $args): RpcRequestInterface
    {
        $invokingMethod = $this->rpcMethodFactory->create($proxy, $method, $args);
        $request = $this->httpRequestFactory->createRequest('POST', $this->createUri($invokingMethod));

        return new JsonRpcRequest($request, $invokingMethod, $this->streamFactory, $this->generateId(), JsonRpcRequestInterface::JSONRPC_VERSION);
    }

    protected function createUri(RpcMethodInterface $method): string
    {
        return $this->baseUri;
    }

    protected function generateId(): int
    {
        $id = $this->id;
        ++$this->id;

        return $id;
    }

    /**
     * @return RequestFactoryInterface
     */
    public function getHttpRequestFactory(): RequestFactoryInterface
    {
        return $this->httpRequestFactory;
    }

    /**
     * @return StreamFactoryInterface
     */
    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * @return RpcMethodFactoryInterface
     */
    public function getRpcMethodFactory(): RpcMethodFactoryInterface
    {
        return $this->rpcMethodFactory;
    }

    /**
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }
}
