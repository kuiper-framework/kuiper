<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

interface RpcExecutorFactoryInterface
{
    /**
     * @param object $proxy
     * @param string $method
     * @param mixed  ...$args
     *
     * @return RpcExecutorInterface
     */
    public function createExecutor(object $proxy, string $method, array $args): RpcExecutorInterface;
}
