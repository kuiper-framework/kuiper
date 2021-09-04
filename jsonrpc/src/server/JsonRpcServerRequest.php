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
    /**
     * @var string
     */
    private $version;

    public function __construct(HttpRequestInterface $httpRequest, RpcMethodInterface $rpcMethod, int $requestId, string $version)
    {
        parent::__construct($httpRequest, $rpcMethod);
        $this->requestId = $requestId;
        $this->version = $version;
    }

    public function getRequestId(): int
    {
        return $this->requestId;
    }

    public function getJsonRpcVersion(): string
    {
        return $this->version;
    }
}
