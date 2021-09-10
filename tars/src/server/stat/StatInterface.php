<?php

declare(strict_types=1);

namespace kuiper\tars\server\stat;

use kuiper\rpc\RpcResponseInterface;

interface StatInterface
{
    public function success(RpcResponseInterface $response, int $responseTime): void;

    public function fail(RpcResponseInterface $response, int $responseTime): void;

    public function timedOut(RpcResponseInterface $response, int $responseTime): void;

    /**
     * @return StatEntry[]
     */
    public function flush(): array;
}
