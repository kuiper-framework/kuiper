<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\rpc\RpcRequestInterface;

class NoOutParamJsonRpcServerResponseFactory extends JsonRpcServerResponseFactory
{
    /**
     * @return mixed
     */
    protected function getResult(RpcRequestInterface $request)
    {
        return $request->getInvokingMethod()->getResult()[0];
    }
}
