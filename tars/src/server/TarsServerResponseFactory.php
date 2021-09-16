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

namespace kuiper\tars\server;

use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\rpc\server\RpcServerResponseFactoryInterface;
use kuiper\tars\core\TarsRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Webmozart\Assert\Assert;

class TarsServerResponseFactory implements RpcServerResponseFactoryInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $httpResponseFactory;
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * JsonRpcServerResponseFactory constructor.
     */
    public function __construct(ResponseFactoryInterface $httpResponseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->httpResponseFactory = $httpResponseFactory;
        $this->streamFactory = $streamFactory;
    }

    public function createResponse(RpcRequestInterface $request): RpcResponseInterface
    {
        Assert::isInstanceOf($request, TarsRequestInterface::class);
        $response = $this->httpResponseFactory->createResponse();

        /** @var TarsRequestInterface $request */
        return new TarsServerResponse($request, $response, $this->streamFactory);
    }
}
