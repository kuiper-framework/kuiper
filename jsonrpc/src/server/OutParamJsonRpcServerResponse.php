<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

class OutParamJsonRpcServerResponse extends JsonRpcServerResponse
{
    /**
     * @return array
     */
    protected function getResult(): array
    {
        return $this->request->getRpcMethod()->getResult();
    }
}
