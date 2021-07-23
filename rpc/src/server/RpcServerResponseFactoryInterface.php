<?php

declare(strict_types=1);

namespace kuiper\rpc\server;

use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;

interface RpcServerResponseFactoryInterface
{
    /**
     * Creates the response.
     */
    public function createResponse(RpcRequestInterface $request): RpcResponseInterface;
}
