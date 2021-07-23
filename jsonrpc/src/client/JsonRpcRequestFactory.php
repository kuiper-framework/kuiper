<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\rpc\client\RpcRequestFactoryInterface;
use kuiper\rpc\InvokingMethod;
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

    public function __construct(RequestFactoryInterface $httpRequestFactory, StreamFactoryInterface $streamFactory, ?int $id = null)
    {
        $this->httpRequestFactory = $httpRequestFactory;
        $this->streamFactory = $streamFactory;
        $this->id = $id ?? random_int(0, 1 << 20);
    }

    public function createRequest(object $proxy, string $method, array $args): RpcRequestInterface
    {
        $invokingMethod = new InvokingMethod($proxy, $method, $args);
        $request = $this->httpRequestFactory->createRequest('POST', $this->createUri($invokingMethod));

        return new JsonRpcRequest($request, $invokingMethod, $this->streamFactory, $this->id++);
    }

    protected function createUri(InvokingMethod $method): string
    {
        return '/';
    }
}
