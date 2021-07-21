<?php

declare(strict_types=1);

namespace kuiper\rpc;

interface MiddlewareInterface
{
    public function process(RequestInterface $request, RequestHandlerInterface $next): ResponseInterface;
}
