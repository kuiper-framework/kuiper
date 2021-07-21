<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\RequestInterface;

interface RequestFactoryInterface
{
    /**
     * Create a new request.
     */
    public function createRequest(object $proxy, string $method, array $args): RequestInterface;
}
