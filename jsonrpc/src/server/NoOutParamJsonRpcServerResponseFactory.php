<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\rpc\RequestInterface;

class NoOutParamJsonRpcServerResponseFactory extends JsonRpcServerResponseFactory
{
    /**
     * @return mixed
     */
    protected function getResult(RequestInterface $request)
    {
        return $request->getInvokingMethod()->getResult()[0];
    }
}
