<?php

declare(strict_types=1);

namespace kuiper\rpc\server;

use Psr\Http\Message\RequestInterface;

interface ServerRequestFactoryInterface
{
    /**
     * Creates the request.
     */
    public function createRequest(RequestInterface $request): \kuiper\rpc\RequestInterface;
}
