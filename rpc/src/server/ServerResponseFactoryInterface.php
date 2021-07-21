<?php

declare(strict_types=1);

namespace kuiper\rpc\server;

use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;

interface ServerResponseFactoryInterface
{
    public function createResponse(RequestInterface $request): ResponseInterface;
}
