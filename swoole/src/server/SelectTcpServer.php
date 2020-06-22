<?php

declare(strict_types=1);
declare(ticks=1);

namespace kuiper\swoole\server;

use kuiper\swoole\ConnectionInfo;
use kuiper\swoole\constants\Event;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\exception\ServerStateException;
use kuiper\swoole\server\workers\ForkedWorkerManager;
use kuiper\swoole\server\workers\SingleWorkerManager;
use kuiper\swoole\server\workers\Task;
use kuiper\swoole\server\workers\WorkerManagerInterface;

class SelectTcpServer extends AbstractServer
{
    /**
     * @var int
     */
    private $masterPid;

    /**
     * @var WorkerManagerInterface
     */
    private $workerManager;

    public static function check(): void
    {
        if (!extension_loaded('pcntl')) {
            throw new \RuntimeException('extension pcntl should be enabled');
        }
        if (!extension_loaded('posix')) {
            throw new \RuntimeException('extension posix should be enabled');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        self::check();
        $this->getSettings()->mergeIfNotExists([
            ServerSetting::BUFFER_OUTPUT_SIZE => 2097152,
            ServerSetting::SOCKET_BUFFER_SIZE => 8192,
            ServerSetting::MAX_CONN => 1000,
            ServerSetting::WORKER_NUM => 1,
            ServerSetting::PACKAGE_MAX_LENGTH => 10485760,
        ]);
        $this->masterPid = getmypid();
        $this->dispatch(Event::BOOTSTRAP, []);
        $this->dispatch(Event::START, []);

        if (1 === $this->getSettings()->getInt(ServerSetting::WORKER_NUM)) {
            $this->workerManager = new SingleWorkerManager($this, $this->logger);
        } else {
            $this->workerManager = new ForkedWorkerManager($this, $this->logger);
        }
        $this->workerManager->loop();

        $this->dispatch(Event::SHUTDOWN, []);
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
        $this->assertMasterProcessAlive();
        posix_kill($this->getMasterPid(), SIGINT);
    }

    /**
     * {@inheritdoc}
     */
    public function reload(): void
    {
        $this->assertMasterProcessAlive();
        posix_kill($this->getMasterPid(), SIGUSR1);
    }

    private function assertMasterProcessAlive(): void
    {
        if (!posix_kill($this->getMasterPid(), 0)) {
            throw new ServerStateException('Master process is not running');
        }
    }

    public function task($data, $taskWorkerId = -1, $onFinish = null)
    {
        $this->workerManager->task($data, $taskWorkerId, $onFinish);
    }

    public function finish($data): void
    {
        $this->workerManager->finish($data);
    }

    public function getMasterPid(): int
    {
        return $this->masterPid;
    }

    public function isTaskWorker(): bool
    {
        return $this->workerManager->isTaskWorker();
    }

    /**
     * {@inheritdoc}
     */
    public function send(int $clientId, string $data): void
    {
        $this->workerManager->send($clientId, $data);
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->workerManager->getResource();
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionInfo(int $clientId): ?ConnectionInfo
    {
        return $this->workerManager->getConnectionInfo($clientId);
    }

    public function tick(int $millisecond, callable $callback): int
    {
        return $this->workerManager->tick($millisecond, $callback);
    }

    protected function close(int $clientId): void
    {
        $this->workerManager->closeConnection($clientId);
    }
}
