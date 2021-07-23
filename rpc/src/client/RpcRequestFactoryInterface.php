<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\RpcRequestInterface;

interface RpcRequestFactoryInterface
{
    /**
     * Create a new request.
     */
    public function createRequest(object $proxy, string $method, array $args): RpcRequestInterface;
}
