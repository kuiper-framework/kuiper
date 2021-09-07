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
        $serviceName = is_string($service) ? $service : get_class($service);

        return new RpcMethod($service, new ServiceLocator($serviceName), $method, $args);
    }
}
