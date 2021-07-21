<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\rpc\HasRequestIdInterface;
use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RpcRequest;
use Psr\Http\Message\RequestInterface;

class JsonRpcRequest extends RpcRequest implements HasRequestIdInterface
{
    public const JSONRPC_VERSION = '2.0';

    /**
     * @var int
     */
    private $requestId;

    public function __construct(int $requestId, RequestInterface $request, InvokingMethod $invokingMethod)
    {
        parent::__construct($request, $invokingMethod);
        $this->requestId = $requestId;
    }

    public function getRequestId(): int
    {
        return $this->requestId;
    }
}
