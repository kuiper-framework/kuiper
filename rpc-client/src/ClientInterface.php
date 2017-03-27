<?php

namespace kuiper\rpc\client;

use ProxyManager\Factory\RemoteObject\AdapterInterface;

interface ClientInterface extends AdapterInterface
{
    const START = 0;
    const CALL = 10;

    /**
     * Add one middleware.
     *
     * The prototype of the callback should match MiddlewareInterface
     *
     * @param callable   $middleware
     * @param string|int $position
     * @param string     $id
     *
     * @return static
     */
    public function add(callable $middleware, $position = self::CALL, $id = null);
}
