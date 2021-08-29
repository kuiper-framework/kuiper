<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\RpcResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @property JsonRpcRequestInterface $request
 */
class JsonRpcServerResponse extends RpcResponse
{
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var StreamInterface|null
     */
    private $body;

    public function __construct(JsonRpcRequestInterface $request, ResponseInterface $httpResponse, StreamFactoryInterface $streamFactory)
    {
        parent::__construct($request, $httpResponse);
        $this->streamFactory = $streamFactory;
    }

    public function getBody(): StreamInterface
    {
        if (null === $this->body) {
            $this->body = $this->streamFactory->createStream(JsonRpcProtocol::encode([
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'id' => $this->request->getRequestId(),
                'result' => $this->getResult(),
            ]));
        }

        return $this->body;
    }

    /**
     * @return mixed
     */
    protected function getResult()
    {
        return $this->getRequest()->getRpcMethod()->getResult()[0];
    }
}
