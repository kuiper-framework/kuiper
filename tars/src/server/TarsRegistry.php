<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\rpc\server\Service;
use kuiper\rpc\server\ServiceRegistryInterface;

class TarsRegistry implements ServiceRegistryInterface
{
    public function register(Service $service): void
    {
        // TODO: Implement register() method.
    }

    public function deregister(Service $service): void
    {
        // TODO: Implement deregister() method.
    }
}
