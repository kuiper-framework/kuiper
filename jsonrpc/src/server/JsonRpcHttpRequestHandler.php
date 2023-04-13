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

use Exception;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcServerRequestInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\ServerRequestHolder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonRpcHttpRequestHandler implements RequestHandlerInterface
{
    /**
     * JsonRpcHttpRequestHandler constructor.
     */
    public function __construct(
        private readonly RpcServerRequestFactoryInterface $requestFactory,
        private readonly RpcRequestHandlerInterface $requestHandler,
        private readonly InvalidRequestHandlerInterface $invalidRequestHandler,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            /** @var JsonRpcRequestInterface|RpcServerRequestInterface $rpcRequest */
            $rpcRequest = $this->requestFactory->createRequest($request);
        } catch (Exception $e) {
            return $this->invalidRequestHandler->handleInvalidRequest($request, $e);
        }
        ServerRequestHolder::setRequest($rpcRequest);

        return $this->requestHandler->handle($rpcRequest);
    }

    /**
     * @return RpcServerRequestFactoryInterface
     */
    public function getRequestFactory(): RpcServerRequestFactoryInterface
    {
        return $this->requestFactory;
    }

    /**
     * @return RpcRequestHandlerInterface
     */
    public function getRequestHandler(): RpcRequestHandlerInterface
    {
        return $this->requestHandler;
    }
}
