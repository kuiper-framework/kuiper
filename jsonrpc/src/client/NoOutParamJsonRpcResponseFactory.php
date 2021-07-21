<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\rpc\InvokingMethod;

class NoOutParamJsonRpcResponseFactory extends JsonRpcResponseFactory
{
    protected function buildResult(InvokingMethod $method, $result): array
    {
        return parent::buildResult($method, [$result]);
    }
}
