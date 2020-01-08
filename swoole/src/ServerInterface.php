<?php

declare(strict_types=1);

namespace kuiper\swoole;

use kuiper\swoole\exception\ServerStateException;
use Swoole\Server;

interface ServerInterface
{
    /**
     * Starts the server.
     */
    public function start(): void;

    /**
     * Stops the server.
     *
     * @throws ServerStateException if fail to stop server
     */
    public function stop(): void;

    /**
     * Gets the internal swoole server.
     */
    public function getSwooleServer(): Server;

    /**
     * Gets the server config.
     */
    public function getServerConfig(): ServerConfig;
}
