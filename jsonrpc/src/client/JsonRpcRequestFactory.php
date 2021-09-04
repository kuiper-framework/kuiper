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
     * @var int
     */
    private $id;
    /**
     * @var RpcMethodFactoryInterface
     */
    private $rpcMethodFactory;

    public function __construct(RequestFactoryInterface $httpRequestFactory, StreamFactoryInterface $streamFactory, RpcMethodFactoryInterface $rpcMethodFactory, ?int $id = null)
    {
        $this->httpRequestFactory = $httpRequestFactory;
        $this->streamFactory = $streamFactory;
        $this->rpcMethodFactory = $rpcMethodFactory;
        $this->id = $id ?? random_int(0, 1 << 20);
    }

    public function createRequest(object $proxy, string $method, array $args): RpcRequestInterface
    {
        $invokingMethod = $this->rpcMethodFactory->create($proxy, $method, $args);
        $request = $this->httpRequestFactory->createRequest('POST', $this->createUri($invokingMethod));

        return new JsonRpcRequest($request, $invokingMethod, $this->streamFactory, $this->id++, JsonRpcRequestInterface::JSONRPC_VERSION);
    }

    protected function createUri(RpcMethodInterface $method): string
    {
        return '/';
    }
}
