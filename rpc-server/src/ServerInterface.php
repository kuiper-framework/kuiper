<?php

namespace kuiper\rpc\server;

use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;

interface ServerInterface
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

    /**
     * Handles request.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return
     */
    public function serve(RequestInterface $request, ResponseInterface $response);
}
