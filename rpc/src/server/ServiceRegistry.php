<?php

declare(strict_types=1);

namespace kuiper\rpc\server;

interface ServiceRegistry
{
    /**
     * @param Service $service
     */
    public function register(Service $service): void;

    /**
     * @param Service $service
     */
    public function deregister(Service $service): void;
}
