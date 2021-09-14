<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface InvalidRequestHandlerInterface
{
    public function handleInvalidRequest(RequestInterface $request, \Exception $exception): ResponseInterface;
}
