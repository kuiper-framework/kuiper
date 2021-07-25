<?php

declare(strict_types=1);

namespace kuiper\rpc;

use kuiper\rpc\exception\InvalidMethodException;

interface RpcMethodFactoryInterface
{
    /**
     * @param object|string $service
     * @param string        $method
     * @param array         $args
     *
     * @return RpcMethodInterface
     *
     * @throws InvalidMethodException
     */
    public function create($service, string $method, array $args): RpcMethodInterface;
}
