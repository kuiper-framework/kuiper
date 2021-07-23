<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use Psr\Http\Message\ResponseInterface;

interface RpcResponseFactoryInterface
{
    /**
     * Creates the rpc response from http response.
     */
    public function createResponse(RpcRequestInterface $request, ResponseInterface $response): RpcResponseInterface;
}
