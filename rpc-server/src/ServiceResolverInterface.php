<?php

namespace kuiper\rpc\server;

interface ServiceResolverInterface
{
    /**
     * Gets the services.
     *
     * @param string $method
     *
     * @return MethodInterface
     *
     * @throws exception\MethodNotFoundException
     */
    public function resolve($method);
}
