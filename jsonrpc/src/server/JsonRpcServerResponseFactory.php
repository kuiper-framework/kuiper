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

namespace kuiper\jsonrpc\server;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\rpc\server\RpcServerResponseFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Webmozart\Assert\Assert;

class JsonRpcServerResponseFactory implements RpcServerResponseFactoryInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $httpResponseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $responseClass = JsonRpcServerResponse::class)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function createResponse(RpcRequestInterface $request): RpcResponseInterface
    {
        Assert::isInstanceOf($request, JsonRpcRequestInterface::class);
        $response = $this->httpResponseFactory->createResponse();
        $class = $this->responseClass;
        /** @var JsonRpcRequestInterface $request */
        return new $class($request, $response->withHeader('content-type', 'application/json'), $this->streamFactory);
    }
}
