<?php

declare(strict_types=1);
declare(ticks=1);

namespace kuiper\swoole\server;

use kuiper\helper\Arrays;
use kuiper\swoole\ConnectionInfo;
use kuiper\swoole\constants\Event;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\exception\ServerStateException;
use kuiper\swoole\server\workers\MessageType;
use kuiper\swoole\server\workers\SocketChannel;
use kuiper\swoole\server\workers\SocketWorker;
use kuiper\swoole\server\workers\Task;
use kuiper\swoole\server\workers\TaskWorker;
use kuiper\swoole\server\workers\WorkerInterface;

class SelectTcpServer extends AbstractServer
{
    /**
     * @var resource
     */
    private $resource;

    /**
     * @var int
     */
    private $masterPid;

    /**
     * @var bool
     */
    private $stopped;

    /**
     * 只在 master 进程中使用.
     *
     * @var WorkerInterface[]
     */
    private $workers = [];

    /**
     * 在 task worker, worker 进程中使用.
     *
     * @var WorkerInterface
     */
    private $worker;

    /**
     * 只在 master 进程.
     *
     * @var int
     */
    private $taskId = 0;

    /**
     * @var \SplQueue
     */
    private $taskQueue;

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
        ]);
        $this->taskQueue = new \SplQueue();
        $this->masterPid = getmypid();
        $this->dispatch(Event::BOOTSTRAP, []);
        $this->dispatch(Event::START, []);

        $this->installSignal();
        try {
            $this->listen();
            while (!$this->stopped) {
                $this->startWorkers();
                $this->handleMessages();
                $this->dispatchTask();
            }
        } catch (\Exception $e) {
            $this->stopWorkers();
        }
        $this->dispatch(Event::SHUTDOWN, []);
        $this->wait();
        fclose($this->resource);
    }

    private function installSignal(): void
    {
        // stop
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
        // reload
        pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
        // ignore
        pcntl_signal(SIGPIPE, SIG_IGN);
        pcntl_signal(SIGCHLD, [$this, 'signalHandler']);
    }

    public function signalHandler($signal): void
    {
        switch ($signal) {
            // Stop.
            case SIGINT:
                $this->stopped = true;
                $this->stopWorkers();
                break;
            // Reload.
            case SIGUSR1:
                $this->stopWorkers();
                break;
            case SIGCHLD:
                $pid = pcntl_waitpid(-1, $status, WNOHANG);
                $this->reaper($pid);
        }
    }

    public function isStopped(): bool
    {
        return $this->stopped;
    }

    protected function listen(): void
    {
        $uri = $this->getUri();
        $socket = stream_socket_server($uri, $errno, $err);

        if (!$socket) {
            throw new ServerStateException("Cannot listen to $uri, code=$errno, message=$err");
        }
        stream_set_blocking($socket, false);
        $this->resource = $socket;
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

    public function task($data, $taskWorkerId = -1, $onFinish = null)
    {
        $this->getSocketWorker()->sendTask($data, $taskWorkerId, $onFinish);
    }

    public function finish($data): void
    {
        $this->getTaskWorker()->finish($data);
    }

    public function getMasterPid(): int
    {
        return $this->masterPid;
    }

    public function isTaskWorker(): bool
    {
        return $this->worker instanceof TaskWorker;
    }

    /**
     * {@inheritdoc}
     */
    public function send(int $clientId, string $data): void
    {
        $this->getSocketWorker()->send($clientId, $data);
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    private function getUri(): string
    {
        $serverPort = $this->getServerConfig()->getPort();

        return sprintf('tcp://%s:%d', $serverPort->getHost(), $serverPort->getPort());
    }

    private function startWorkers(): void
    {
        $taskWorkerNum = $this->getSettings()->getInt(ServerSetting::TASK_WORKER_NUM);
        if ($taskWorkerNum > 0) {
            $this->doStartWorkers(TaskWorker::class, $taskWorkerNum, 0);
        }
        $workerNum = $this->getSettings()->getInt(ServerSetting::WORKER_NUM);
        if ($workerNum > 0) {
            $this->doStartWorkers(SocketWorker::class, $workerNum, $taskWorkerNum);
        }
    }

    private function doStartWorkers($workerType, $num, $startId): void
    {
        for ($i = 0; $i < $num; ++$i) {
            $workerId = $i + $startId;
            if (isset($this->workers[$workerId])) {
                $pid = $this->workers[$workerId]->getPid();
                if (posix_kill($pid, 0)) {
                    continue;
                }
            }

            $channel = new SocketChannel();
            $pid = pcntl_fork();
            if (-1 == $pid) {
                throw new \RuntimeException('Cannot fork queue worker');
            }
            /** @var WorkerInterface $worker */
            $worker = new $workerType($this, $channel, $pid, $workerId, $this->logger);
            if (0 === $pid) {
                $this->worker = $worker;
                $worker->start();
                exit;
            }
            $channel->parent();
            $this->workers[$workerId] = $worker;
        }
    }

    private function stopWorkers(): void
    {
        foreach ($this->workers as $worker) {
            posix_kill($worker->getPid(), SIGINT);
        }
    }

    private function wait(): void
    {
        $timeout = 30;
        while ($this->workers && $timeout > 0) {
            --$timeout;
            sleep(1);
        }
        if ($this->workers) {
            throw new ServerStateException('workers still alive, pid='.implode(',', Arrays::pull($this->workers, 'pid')));
        }
    }

    protected function assertMasterProcessAlive(): void
    {
        if (!isset($this->masterPid) || !posix_kill($this->masterPid, 0)) {
            throw new ServerStateException('Master process is not running');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionInfo(int $clientId): ?ConnectionInfo
    {
        return $this->getSocketWorker()->getConnectionInfo($clientId);
    }

    protected function close($socket): void
    {
        $this->getSocketWorker()->close($socket);
    }

    private function reaper(int $pid): void
    {
        while ($pid > 0) {
            foreach ($this->workers as $i => $worker) {
                if ($worker->getPid() === $pid) {
                    unset($this->workers[$i]);
                }
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
    }

    private function getSocketWorker(): SocketWorker
    {
        if (!$this->worker || !$this->worker instanceof SocketWorker) {
            throw new \InvalidArgumentException('cannot send without worker');
        }

        return $this->worker;
    }

    private function getSocketWorkerByWorkerId(int $workerId): SocketWorker
    {
        if (isset($this->workers[$workerId])
            && $this->workers[$workerId] instanceof SocketWorker) {
            return $this->workers[$workerId];
        }
        throw new \InvalidArgumentException("cannot find worker id=$workerId");
    }

    private function getTaskWorker(): TaskWorker
    {
        if (!$this->worker || !$this->worker instanceof TaskWorker) {
            throw new \InvalidArgumentException('not in task worker');
        }

        return $this->worker;
    }

    private function getTaskWorkerByWorkerId(int $taskWorkerId): TaskWorker
    {
        if (isset($this->workers[$taskWorkerId])
            && $this->workers[$taskWorkerId] instanceof TaskWorker) {
            return $this->workers[$taskWorkerId];
        }
        throw new \InvalidArgumentException("cannot find task worker id=$taskWorkerId");
    }

    private function handleMessages(): void
    {
        if (!$this->workers) {
            return;
        }
        $channels = SocketChannel::select(array_map(static function (WorkerInterface $worker) {
            return $worker->getChannel();
        }, $this->workers));
        foreach ($channels as $channel) {
            $data = $channel->read();
            if (2 !== count($data)) {
                throw new \InvalidArgumentException('invalid message');
            }
            switch ($data[0]) {
                case MessageType::TASK:
                    $this->taskQueue->push($data[1]);
                    break;
                case MessageType::TASK_RESULT:
                    $this->sendTaskResult($data[1]);
                    break;
                case MessageType::TASK_FINISH:
                    $this->onTaskFinished($data[1]);
                    break;
            }
        }
    }

    private function dispatchTask(): void
    {
        if ($this->taskQueue->isEmpty()) {
            return;
        }

        $availableWorkers = [];
        foreach ($this->workers as $worker) {
            if ($worker instanceof TaskWorker && $worker->isIdle()) {
                $availableWorkers[$worker->getWorkerId()] = $worker;
            }
        }
        $tasks = [];
        while (!$this->taskQueue->isEmpty() && $availableWorkers) {
            /** @var Task $task */
            $task = $this->taskQueue->pop();
            $taskWorker = null;
            if ($task->getTaskWorkerId() > 0) {
                if (isset($availableWorkers[$task->getTaskWorkerId()])) {
                    $taskWorker = $availableWorkers[$task->getTaskWorkerId()];
                } else {
                    $tasks[] = $task;
                }
            } else {
                $taskWorker = current($availableWorkers);
            }
            if ($taskWorker) {
                $task->setTaskId($this->taskId++);
                $task->setTaskWorkerId($taskWorker->getWorkerId());
                $taskWorker->setTask($task);
                unset($availableWorkers[$taskWorker->getWorkerId()]);
            }
        }
        foreach ($tasks as $task) {
            $this->taskQueue->push($task);
        }
    }

    private function sendTaskResult(Task $task): void
    {
        $this->getSocketWorkerByWorkerId($task->getFromWorkerId())->getChannel()->send([MessageType::TASK_RESULT, $task]);
    }

    private function onTaskFinished(Task $task): void
    {
        $this->getTaskWorkerByWorkerId($task->getTaskWorkerId())->done();
        $this->getSocketWorkerByWorkerId($task->getFromWorkerId())->getChannel()->send([MessageType::TASK_FINISH, $task]);
    }
}
