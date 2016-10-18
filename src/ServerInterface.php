<?php
namespace kuiper\rpc\server;

use kuiper\rpc\server\request\RequestInterface;

interface ServerInterface
{
    /**
     * Adds service
     * 
     * @param string|object $service
     * @param string $name
     * @return static
     */
    public function add($service, $name = null);

    /**
     * Handles request
     *
     * @param request\RequestInterface
     * @return response\ResponseInterface
     */
    public function handle(RequestInterface $request);
}
