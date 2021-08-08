<?php

declare(strict_types=1);

namespace kuiper\swoole\server;

use kuiper\swoole\ConnectionInfo;
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
     *
     * @throws ServerStateException
     */
    public function reload(): void;

    /**
     * Stops the server.
     *
     * @throws ServerStateException
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
     * @param int      $taskWorkerId
     * @param callable $onFinish
     *
     * @return mixed
     */
    public function task($data, $taskWorkerId = -1, $onFinish = null);

    /**
     * Finish task and return data.
     *
     * @param mixed $data
     */
    public function finish($data): void;

    /**
     * @param int      $millisecond
     * @param callable $callback
     *
     * @return int
     */
    public function tick(int $millisecond, callable $callback): int;

    /**
     * @param int      $millisecond
     * @param callable $callback
     *
     * @return int
     */
    public function after(int $millisecond, callable $callback): int;

    /**
     * @return ConnectionInfo
     */
    public function getConnectionInfo(int $clientId): ?ConnectionInfo;

    /**
     * @param string $message
     * @param int    $workerId
     */
    public function sendMessage(string $message, int $workerId): void;

    /**
     * @param string $message
     */
    public function sendMessageToAll(string $message): void;
}
