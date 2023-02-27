<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\swoole\server\workers;

use Exception;
use InvalidArgumentException;
use kuiper\helper\Arrays;
use kuiper\swoole\ConnectionInfo;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\exception\ServerStateException;
use RuntimeException;
use SplQueue;

class ForkedWorkerManager extends AbstractWorkerManager
{
    protected const TAG = '['.__CLASS__.'] ';

    private int $taskWorkerNum = 0;

    private int $workerNum = 0;

    /**
     * @var WorkerInterface[]
     */
    private array $workers;

    private ?WorkerInterface $worker = null;

    private ?SplQueue $taskQueue = null;

    private int $taskId = 0;

    /**
     * @throws ServerStateException
     */
    public function loop(): void
    {
        $this->installSignal();
        $settings = $this->getServerConfig()->getSettings();
        $this->workerNum = $settings->getInt(ServerSetting::WORKER_NUM);
        $this->taskWorkerNum = $settings->getInt(ServerSetting::TASK_WORKER_NUM);
        $this->taskQueue = new SplQueue();

        try {
            $this->listen();
            while (!$this->isStopped()) {
                $this->startWorkers();
                $this->handleMessages();
                $this->dispatchTask();
                sleep(1);
            }
        } catch (Exception $e) {
            $this->logger->error(static::TAG.'start fail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->stopWorkers();
        }
        $this->wait();
        $this->close();
    }

    public function task($data, $taskWorkerId = -1, $onFinish = null): void
    {
        $this->getSocketWorker()->sendTask($data, $taskWorkerId, $onFinish);
    }

    public function finish($data): void
    {
        $this->getTaskWorker()->finish($data);
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

    protected function getUri(): string
    {
        $serverPort = $this->getServerConfig()->getPort();

        return sprintf('tcp://%s:%d', $serverPort->getHost(), $serverPort->getPort());
    }

    private function startWorkers(): void
    {
        if ($this->taskWorkerNum > 0) {
            $this->doStartWorkers(TaskWorker::class, $this->taskWorkerNum, 0);
        }
        if ($this->workerNum > 0) {
            $this->doStartWorkers(SocketWorker::class, $this->workerNum, $this->taskWorkerNum);
        }
    }

    private function doStartWorkers(string $workerType, int $num, int $startId): void
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
            if (-1 === $pid) {
                throw new RuntimeException('Cannot fork queue worker');
            }
            /** @var WorkerInterface $worker */
            $worker = new $workerType($this, $channel, $pid, $workerId, $this->logger);
            if (0 === $pid) {
                $this->logger->debug(static::TAG.'start worker', [
                    'workerId' => $workerId, 'worker' => $workerType,
                ]);
                $this->worker = $worker;
                try {
                    $worker->start();
                } catch (Exception $e) {
                    $this->logger->error(static::TAG.'Cannot start worker', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(), ]);
                }
                $this->logger->debug(static::TAG.'worker exit');
                exit;
            }
            $channel->parent();
            if (!$channel->isActive()) {
                throw new RuntimeException('Cannot create channel');
            }
            $this->workers[$workerId] = $worker;
        }
    }

    private function stopWorkers(): void
    {
        foreach ($this->workers as $worker) {
            posix_kill($worker->getPid(), SIGINT);
        }
    }

    /**
     * @throws ServerStateException
     */
    private function wait(): void
    {
        $timeout = 30;
        while (!empty($this->workers) && $timeout > 0) {
            --$timeout;
            sleep(1);
        }
        if (!empty($this->workers)) {
            throw new ServerStateException('workers still alive, pid='.implode(',', Arrays::pull($this->workers, 'pid')));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionInfo(int $clientId): ?ConnectionInfo
    {
        return $this->getSocketWorker()->getConnectionInfo($clientId);
    }

    public function closeConnection(int $clientId): void
    {
        $this->getSocketWorker()->close($clientId);
    }

    private function reaper(int $pid): void
    {
        while ($pid > 0) {
            foreach ($this->workers as $i => $worker) {
                if ($worker->getPid() === $pid) {
                    $this->logger->info(static::TAG."reaper pid $pid");
                    unset($this->workers[$i]);
                }
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
    }

    private function getSocketWorker(): SocketWorker
    {
        if (!$this->worker instanceof SocketWorker) {
            throw new InvalidArgumentException('cannot send without worker');
        }

        return $this->worker;
    }

    private function getSocketWorkerByWorkerId(int $workerId): SocketWorker
    {
        if (isset($this->workers[$workerId])
            && $this->workers[$workerId] instanceof SocketWorker) {
            return $this->workers[$workerId];
        }
        throw new InvalidArgumentException("cannot find worker id=$workerId");
    }

    private function getTaskWorker(): TaskWorker
    {
        if (!$this->worker instanceof TaskWorker) {
            throw new InvalidArgumentException('not in task worker: '.(isset($this->worker) ? get_class($this->worker) : 'null'));
        }

        return $this->worker;
    }

    private function getTaskWorkerByWorkerId(int $taskWorkerId): TaskWorker
    {
        if (isset($this->workers[$taskWorkerId])
            && $this->workers[$taskWorkerId] instanceof TaskWorker) {
            return $this->workers[$taskWorkerId];
        }
        throw new InvalidArgumentException("cannot find task worker id=$taskWorkerId");
    }

    private function handleMessages(): void
    {
        if (empty($this->workers)) {
            return;
        }
        while (true) {
            $channels = array_filter(array_map(static function (WorkerInterface $worker): SocketChannel {
                return $worker->getChannel();
            }, $this->workers));
            $channels = SocketChannel::select($channels, 0);
            if (empty($channels)) {
                break;
            }
            $this->logger->debug(static::TAG.'select worker channels', ['channels' => $channels]);
            foreach ($channels as $channel) {
                $data = $channel->read();
                $this->logger->debug(static::TAG.'read data', ['data' => $data]);
                if (!is_array($data) || 2 !== count($data)) {
                    $this->logger->error(static::TAG.'read invalid message from channel', ['data' => $data]);
                    continue;
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
    }

    private function dispatchTask(): void
    {
        $this->sendTick();
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
        while (!$this->taskQueue->isEmpty() && !empty($availableWorkers)) {
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
            if (null !== $taskWorker) {
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
        $this->getSocketWorkerByWorkerId($task->getFromWorkerId())->getChannel()->push([MessageType::TASK_RESULT, $task]);
    }

    private function onTaskFinished(Task $task): void
    {
        $this->getTaskWorkerByWorkerId($task->getTaskWorkerId())->done();
        $this->getSocketWorkerByWorkerId($task->getFromWorkerId())->getChannel()->push([MessageType::TASK_FINISH, $task]);
    }

    public function tick(int $millisecond, callable $callback): int
    {
        if (null === $this->worker) {
            throw new InvalidArgumentException('Cannot call tick on master process');
        }

        return $this->worker->tick($millisecond, $callback);
    }

    public function after(int $millisecond, callable $callback): int
    {
        if (null === $this->worker) {
            throw new InvalidArgumentException('Cannot call tick on master process');
        }

        return $this->worker->after($millisecond, $callback);
    }

    private function sendTick(): void
    {
        foreach ($this->workers as $worker) {
            if ($worker->getChannel()->isActive()) {
                $worker->getChannel()->push([MessageType::TICK, null]);
            } else {
                $this->logger->error(static::TAG.'worker channel is closed', ['worker' => $worker->getWorkerId()]);
            }
        }
    }

    public function signalHandler(int $signal): void
    {
        switch ($signal) {
            // Stop.
            case SIGINT:
                $this->stop();
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
}
