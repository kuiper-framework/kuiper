<?php

namespace kuiper\rpc\server;

interface MiddlewareInterface
{
    /**
     * RPC server middleware.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param callable          $next
     *
     * @return ResponseInterface
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next);
}
