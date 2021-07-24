<?php

declare(strict_types=1);

namespace kuiper\rpc;

interface MiddlewareInterface
{
    /**
     * @param RpcRequestInterface        $request
     * @param RpcRequestHandlerInterface $handler
     *
     * @return RpcResponseInterface
     */
    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface;
}
