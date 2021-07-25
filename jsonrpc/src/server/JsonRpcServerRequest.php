<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcRequest;
use Psr\Http\Message\RequestInterface as HttpRequestInterface;

class JsonRpcServerRequest extends RpcRequest implements JsonRpcRequestInterface
{
    /**
     * @var int
     */
    private $requestId;

    public function __construct(HttpRequestInterface $httpRequest, RpcMethodInterface $rpcMethod, int $requestId)
    {
        parent::__construct($httpRequest, $rpcMethod);
        $this->requestId = $requestId;
    }

    public function getRequestId(): int
    {
        return $this->requestId;
    }
}
