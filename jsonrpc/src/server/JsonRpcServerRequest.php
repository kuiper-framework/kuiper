<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RpcRpcRequest;
use Psr\Http\Message\RequestInterface as HttpRequestInterface;

class JsonRpcServerRequest extends RpcRpcRequest implements JsonRpcRequestInterface
{
    /**
     * @var int
     */
    private $requestId;

    public function __construct(HttpRequestInterface $httpRequest, InvokingMethod $invokingMethod, int $requestId)
    {
        parent::__construct($httpRequest, $invokingMethod);
        $this->requestId = $requestId;
    }

    public function getRequestId(): int
    {
        return $this->requestId;
    }
}
