<?php

declare(strict_types=1);

namespace kuiper\rpc;

interface ErrorHandlerInterface
{
    public function handle(RpcRequestInterface $request, \Throwable $error): RpcResponseInterface;
}
