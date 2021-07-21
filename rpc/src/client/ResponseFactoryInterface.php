<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ResponseFactoryInterface
{
    public function createResponse(RequestInterface $request, ResponseInterface $response): \kuiper\rpc\ResponseInterface;
}
