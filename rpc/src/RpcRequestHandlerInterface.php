<?php

declare(strict_types=1);

namespace kuiper\rpc;

interface RpcRequestHandlerInterface
{
    /**
     * @throws \Exception
     */
    public function handle(RpcRequestInterface $request): RpcResponseInterface;
}
