<?php

declare(strict_types=1);

namespace kuiper\swoole\server;

use kuiper\swoole\exception\ServerStateException;
use kuiper\swoole\ServerConfig;

interface ServerInterface
{
    /**
     * Starts the server.
     *
     * @throws ServerStateException
     */
    public function start(): void;

    /**
     * Reload the server.
     */
    public function reload(): void;

    /**
     * Stops the server.
     */
    public function stop(): void;

    /**
     * Gets the master process id.
     */
    public function getMasterPid(): int;

    /**
     * Gets the server config.
     */
    public function getServerConfig(): ServerConfig;

    /**
     * Sends data to client.
     */
    public function send(int $clientId, string $data): void;

    /**
     * Check if it is task worker.
     */
    public function isTaskWorker(): bool;

    /**
     * Send task.
     *
     * @param mixed    $data
     * @param int      $workerId
     * @param callable $onFinish
     *
     * @return mixed
     */
    public function task($data, $workerId = -1, $onFinish = null);

    /**
     * Finish task and return data.
     *
     * @param mixed $data
     */
    public function finish($data): void;
}
