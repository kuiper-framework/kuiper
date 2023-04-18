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

use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class JsonRpcServerResponse extends RpcResponse
{
    private ?StreamInterface $body = null;

    public function __construct(
        RpcRequestInterface $request,
        ResponseInterface $httpResponse,
        private readonly StreamFactoryInterface $streamFactory
    ) {
        parent::__construct($request, $httpResponse);
    }

    public function withBody(StreamInterface $body)
    {
        $copy = clone $this;
        $copy->body = $body;

        return $copy;
    }

    public function getBody(): StreamInterface
    {
        if (null === $this->body) {
            $this->body = $this->streamFactory->createStream(JsonRpcProtocol::encode([
                JsonRpcProtocol::EXTENDED => true,
                'jsonrpc' => JsonRpcProtocol::VERSION,
                'id' => $this->getRequest()->getRequestId(),
                'result' => $this->getResult(),
            ]));
        }

        return $this->body;
    }

    /**
     * @return mixed
     */
    protected function getResult(): mixed
    {
        return $this->getRequest()->getRpcMethod()->getResult();
    }
}
