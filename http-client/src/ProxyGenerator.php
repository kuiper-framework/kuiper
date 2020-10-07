<?php

declare(strict_types=1);

namespace kuiper\http\client;

interface ProxyGenerator
{
    /**
     * Generates client proxy class.
     *
     * @param string $clientClassName the client interface class name
     *
     * @return string the proxy class name
     */
    public function generate(string $clientClassName): string;
}
