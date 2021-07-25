<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\RpcMethod;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcMethodInterface;

class JsonRpcMethodFactory implements RpcMethodFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create($service, string $method, array $args): RpcMethodInterface
    {
        $interfaceName = ProxyGenerator::getInterfaceName(is_string($service) ? $service : get_class($service));
        $serviceName = str_replace('\\', '.', $interfaceName);

        return new RpcMethod($service, $serviceName, $method, $args);
    }
}
