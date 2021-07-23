<?php

declare(strict_types=1);

namespace kuiper\rpc\server;

use kuiper\rpc\exception\InvalidRequestException;
use kuiper\rpc\RpcRequestInterface;
use Psr\Http\Message\RequestInterface;

interface RpcServerRequestFactoryInterface
{
    /**
     * Creates the request.
     *
     * @throws InvalidRequestException
     */
    public function createRequest(RequestInterface $request): RpcRequestInterface;
}
