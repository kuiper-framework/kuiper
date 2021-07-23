<?php

declare(strict_types=1);

namespace kuiper\swoole\server\workers;

use kuiper\helper\Properties;
use kuiper\swoole\ConnectionInfo;
use kuiper\swoole\event\AbstractServerEvent;
use kuiper\swoole\ServerConfig;

interface WorkerManagerInterface
{
    /**
     * starts loop.
     */
    public function loop(): void;

    /**
     * @return mixed
     */
    public function getResource();

    /**
     * Sends data to client.
     */
    public function send(int $clientId, string $data): void;

    /**
     * Closes client connection.
     */
    public function closeConnection(int $clientId): void;

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
     * @return void
     */
    public function task($data, $taskWorkerId = -1, $onFinish = null);

    /**
     * Finish task and return data.
     *
     * @param mixed $data
     */
    public function finish($data): void;

    /**
     * Adds timer.
     */
    public function tick(int $millisecond, callable $callback): int;

    public function after(int $millisecond, callable $callback): int;

    /**
     * @return ConnectionInfo
     */
    public function getConnectionInfo(int $clientId): ?ConnectionInfo;

    public function dispatch(string $event, array $args): ?AbstractServerEvent;

    public function getServerConfig(): ServerConfig;

    public function getSettings(): Properties;
}
