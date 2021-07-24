<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

class OutParamJsonRpcServerResponse extends JsonRpcServerResponse
{
    /**
     * @return mixed
     */
    protected function getResult()
    {
        return $this->request->getInvokingMethod()->getResult();
    }
}
