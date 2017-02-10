<?php

namespace kuiper\rpc\server;

interface ServerInterface
{
    /**
     * Add one middleware.
     *
     * The prototype of the callback should match MiddlewareInterface
     *
     * @param callable $callback
     *
     * @return static
     */
    public function add(callable $callback);

    /**
     * Handles request.
     *
     * @param RequestInterface $request
     */
    public function serve(RequestInterface $request, ResponseInterface $response);
}
