<?php

namespace kuiper\rpc\client;

use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;

interface HandlerInterface
{
    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, ResponseInterface $response);
}
