<?php

declare(strict_types=1);

namespace kuiper\tars\client;

use kuiper\rpc\client\RpcRequestFactoryInterface;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\tars\core\TarsMethodInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class TarsRequestFactory implements RpcRequestFactoryInterface
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
        /** @var TarsMethodInterface $rpcMethod */
        $rpcMethod = $this->rpcMethodFactory->create($proxy, $method, $args);
        $request = $this->httpRequestFactory->createRequest('POST', '/');

        return new TarsRequest($request, $rpcMethod, $this->streamFactory, $this->id++);
    }
}
