<?php

declare(strict_types=1);

namespace kuiper\tars\client;

use kuiper\rpc\client\RequestFactoryInterface;
use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RequestInterface;
use kuiper\tars\core\MethodMetadataFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class TarsRpcRequestFactory implements RequestFactoryInterface
{
    /**
     * @var \Psr\Http\Message\RequestFactoryInterface
     */
    private $httpRequestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var MethodMetadataFactoryInterface
     */
    private $methodMetadataFactory;

    /**
     * @var int
     */
    private $id;

    public function __construct(\Psr\Http\Message\RequestFactoryInterface $httpRequestFactory, StreamFactoryInterface $streamFactory, MethodMetadataFactoryInterface $methodMetadataFactory, ?int $id = null)
    {
        $this->httpRequestFactory = $httpRequestFactory;
        $this->streamFactory = $streamFactory;
        $this->methodMetadataFactory = $methodMetadataFactory;
        $this->id = $id ?? random_int(0, 1 << 20);
    }

    public function createRequest(object $proxy, string $method, array $args): RequestInterface
    {
        $invokingMethod = new InvokingMethod($proxy, $method, $args);
        $methodMetadata = $this->methodMetadataFactory->create($proxy, $method);
        $request = $this->httpRequestFactory->createRequest('POST', '/');

        return new TarsRpcRequest($request, $invokingMethod, $this->id++, $methodMetadata, $this->streamFactory);
    }
}
