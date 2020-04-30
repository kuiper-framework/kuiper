<?php

declare(strict_types=1);

namespace kuiper\swoole\server;

interface ServerInterface
{
    /**
     * Starts the server.
     */
    public function start(): void;
}
