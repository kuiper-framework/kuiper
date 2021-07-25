<?php

declare(strict_types=1);

namespace kuiper\rpc;

class RpcMethodFactory implements RpcMethodFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create($service, string $method, array $args): RpcMethodInterface
    {
        return new RpcMethod($service, null, $method, $args);
    }
}
