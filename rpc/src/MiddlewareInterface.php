<?php

declare(strict_types=1);

namespace kuiper\rpc;

interface MiddlewareInterface
{
    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $next): RpcResponseInterface;
}
