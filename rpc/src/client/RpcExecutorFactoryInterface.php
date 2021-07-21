<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

interface RpcExecutorFactoryInterface
{
    /**
     * @param mixed ...$args
     */
    public function createExecutor(string $method, ...$args): RpcExecutorInterface;
}
