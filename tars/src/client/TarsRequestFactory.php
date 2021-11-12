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

namespace kuiper\tars\client;

use kuiper\rpc\client\RequestIdGeneratorInterface;
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
     * @var string|null
     */
    private $baseUri;

    /**
     * @var RpcMethodFactoryInterface
     */
    private $rpcMethodFactory;

    /**
     * @var RequestIdGeneratorInterface
     */
    private $requestIdGenerator;

    public function __construct(RequestFactoryInterface $httpRequestFactory, StreamFactoryInterface $streamFactory, RpcMethodFactoryInterface $rpcMethodFactory, RequestIdGeneratorInterface $requestIdGenerator, string $baseUri = null)
    {
        $this->httpRequestFactory = $httpRequestFactory;
        $this->streamFactory = $streamFactory;
        $this->rpcMethodFactory = $rpcMethodFactory;
        $this->baseUri = $baseUri;
        $this->requestIdGenerator = $requestIdGenerator;
    }

    public function createRequest(object $proxy, string $method, array $args): RpcRequestInterface
    {
        /** @var TarsMethodInterface $rpcMethod */
        $rpcMethod = $this->rpcMethodFactory->create($proxy, $method, $args);
        $request = $this->httpRequestFactory->createRequest('POST', $this->baseUri ?? '/');

        return new TarsRequest($request, $rpcMethod, $this->streamFactory, $this->requestIdGenerator->next());
    }
}
