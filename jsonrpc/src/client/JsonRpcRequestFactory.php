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

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\client\RequestIdGeneratorInterface;
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
     * @var RequestIdGeneratorInterface
     */
    private $requestIdGenerator;

    public function __construct(RequestFactoryInterface $httpRequestFactory, StreamFactoryInterface $streamFactory, RpcMethodFactoryInterface $rpcMethodFactory, RequestIdGeneratorInterface $requestIdGenerator, string $baseUri = '/')
    {
        $this->httpRequestFactory = $httpRequestFactory;
        $this->streamFactory = $streamFactory;
        $this->rpcMethodFactory = $rpcMethodFactory;
        $this->baseUri = $baseUri;
        $this->requestIdGenerator = $requestIdGenerator;
    }

    public function createRequest(object $proxy, string $method, array $args): RpcRequestInterface
    {
        $invokingMethod = $this->rpcMethodFactory->create($proxy, $method, $args);
        $request = $this->httpRequestFactory->createRequest('POST', $this->createUri($invokingMethod));

        return new JsonRpcRequest($request, $invokingMethod, $this->streamFactory, $this->requestIdGenerator->next(), JsonRpcRequestInterface::JSONRPC_VERSION);
    }

    protected function createUri(RpcMethodInterface $method): string
    {
        return $this->baseUri;
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
