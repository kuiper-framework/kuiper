<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\rpc\RpcMethodInterface;

class NoOutParamJsonRpcResponseFactory extends JsonRpcResponseFactory
{
    protected function buildResult(RpcMethodInterface $method, $result): array
    {
        return parent::buildResult($method, [$result]);
    }
}
