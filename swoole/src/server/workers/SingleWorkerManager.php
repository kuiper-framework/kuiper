<?php

declare(strict_types=1);

namespace kuiper\swoole\server\workers;

use kuiper\swoole\ConnectionInfo;
use kuiper\swoole\constants\Event;
use kuiper\swoole\constants\ServerSetting;

class SingleWorkerManager extends AbstractWorkerManager
{
    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var resource[]
     */
    private $sockets;

    /**
     * @var array
     */
    private $clients;

    /**
     * @var int
     */
    private $socketBufferSize;

    /**
     * @var int
     */
    private $bufferOutputSize;

    /**
     * @var int
     */
    private $maxConnections;

    /**
     * @var array
     */
    private $taskCallbacks;

    /**
     * @var \SplQueue
     */
    private $taskQueue;

    /**
     * @var int
     */
    private $taskCallbackId = 0;
    /**
     * @var int
     */
    private $taskId = 0;
    /**
     * @var Task
     */
    private $currentTask;

    /**
     * @var int
     */
    private $timerId = 0;

    /**
     * @var \SplPriorityQueue
     */
    private $timerCallbacks;

    public function loop(): void
    {
        $this->installSignal();
        $settings = $this->getServerConfig()->getSettings();
        $this->socketBufferSize = $settings->getInt(ServerSetting::SOCKET_BUFFER_SIZE);
        $this->maxConnections = $settings->getInt(ServerSetting::MAX_CONN);
        $this->bufferOutputSize = $settings->getInt(ServerSetting::BUFFER_OUTPUT_SIZE);
        $this->taskQueue = new \SplQueue();
        $this->timerCallbacks = new \SplPriorityQueue();

        try {
            $this->listen();
            $this->dispatch(Event::WORKER_START, [$this->getWorkerId()]);
            $socket = $this->getResource();
            $this->sockets[(int) $socket] = $socket;
            $this->setErrorHandler();
            while (!$this->isStopped()) {
                pcntl_signal_dispatch();
                $this->select();
                $this->triggerTick();
                $this->dispatchTask();
            }
            $this->restoreErrorHandler();
            $this->dispatch(Event::WORKER_STOP, [$this->getWorkerId()]);
        } catch (\Exception $e) {
            $this->logger->error(static::TAG.'start fail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            $this->close();
        }
    }

    private function select(): void
    {
        $read = $this->sockets;
        $write = $except = null;
        if (stream_select($read, $write, $except, 0, 200000)) {
            foreach ($read as $socket) {
                if ($socket === $this->getResource()) {
                    if ($clientSocketId = $this->accept()) {
                        $this->dispatch(Event::CONNECT, [$clientSocketId, 0]);
                    }
                } else {
                    $data = $this->read($socket, $this->socketBufferSize);
                    if (!empty($data)) {
                        $this->clients[(int) $socket]['last_time'] = time();
                        $this->dispatch(Event::RECEIVE, [(int) $socket, 0, $data]);
                    } else {
                        $this->closeConnection((int) $socket);
                    }
                }
            }
        }
    }

    private function setErrorHandler(): void
    {
        set_error_handler([$this, 'handleError']);
    }

    public function handleError(): void
    {
        $this->logger->error(static::TAG.'socket error', ['error' => func_get_args()]);
    }

    private function restoreErrorHandler(): void
    {
        restore_error_handler();
    }

    private function triggerTick(): void
    {
        $time = time();
        while (!$this->timerCallbacks->isEmpty()) {
            /** @var Timer $top */
            $top = $this->timerCallbacks->top();
            if ($top->getTriggerTime() > $time) {
                break;
            }
            /** @var Timer $timer */
            $timer = $this->timerCallbacks->extract();
            $timer->trigger();
            if (!$timer->isOnce()) {
                $this->timerCallbacks->insert($timer, $timer->getTriggerTime());
            }
        }
    }

    private function dispatchTask(): void
    {
        if ($this->taskQueue->isEmpty()) {
            return;
        }

        while (!$this->taskQueue->isEmpty()) {
            /** @var Task $task */
            $task = $this->taskQueue->pop();
            try {
                $task->setTaskId($this->taskId++);
                $task->setTaskWorkerId($this->getWorkerId());
                $this->currentTask = $task;
                $this->dispatch(Event::TASK, [$task->getTaskId(), $task->getFromWorkerId(), $task->getData()]);
            } finally {
                $this->currentTask = null;
            }
        }
    }

    private function read($fp, $length): string
    {
        $data = '';
        while ($buf = fread($fp, $length)) {
            $data .= $buf;
            if (strlen($buf) < $length) {
                break;
            }
        }

        return $data;
    }

    public function closeConnection(int $socketId): void
    {
        if (isset($this->sockets[$socketId])) {
            fclose($this->sockets[$socketId]);
        }
        $this->sockets[$socketId] = null;
        unset($this->sockets[$socketId], $this->clients[$socketId]);
        $this->dispatch(Event::CLOSE, [$socketId, 0]);
    }

    /**
     * @return false|int
     */
    private function accept()
    {
        $socket = stream_socket_accept($this->getResource(), 0);
        //惊群
        if (false === $socket) {
            return false;
        }
        $socketId = (int) $socket;
        stream_set_blocking($socket, false);
        $this->clients[$socketId]['connect_time'] = time();
        $this->sockets[$socketId] = $socket;
        if (count($this->clients) > $this->maxConnections) {
            fclose($socket);

            return false;
        }

        // 设置写缓冲区
        stream_set_write_buffer($socket, $this->bufferOutputSize);

        return $socketId;
    }

    public function signalHandler(int $signal): void
    {
        $this->logger->info(static::TAG.'receive signal', ['signal' => $signal]);
        switch ($signal) {
            // Stop.
            case SIGINT:
            case SIGUSR1:
                $this->stop();
                break;
        }
    }

    public function send(int $clientId, string $data): void
    {
        if (!isset($this->sockets[$clientId]) || $clientId === $this->getResourceId()) {
            return;
        }
        $fp = $this->sockets[$clientId];
        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $ret = fwrite($fp, substr($data, $written));
            if (false === $ret || $ret <= 0) {
                return;
            }
            $written += $ret;
        }
    }

    public function isTaskWorker(): bool
    {
        return true;
    }

    public function getWorkerId(): int
    {
        return 0;
    }

    public function task($data, $taskWorkerId = -1, $onFinish = null)
    {
        $task = new Task();
        $task->setFromWorkerId($this->getWorkerId());
        $task->setCallbackId($this->taskCallbackId++);
        $task->setTaskWorkerId($taskWorkerId);
        $task->setData($data);
        $this->taskCallbacks[$task->getCallbackId()] = $onFinish;
        $this->taskQueue->push($task);
    }

    public function finish($data): void
    {
        if (isset($this->currentTask, $this->taskCallbacks[$this->currentTask->getCallbackId()])) {
            call_user_func($this->taskCallbacks[$this->currentTask->getCallbackId()], $data);
        }
    }

    public function tick(int $millisecond, callable $callback): int
    {
        return $this->addTimer($millisecond, $callback, false);
    }

    public function after(int $millisecond, callable $callback): int
    {
        return $this->addTimer($millisecond, $callback, true);
    }

    private function addTimer(int $millisecond, callable $callback, bool $once): int
    {
        $second = (int) ($millisecond / 1000);
        if ($second <= 0) {
            $second = 1;
        }
        $timer = new Timer($this->timerId++, $second, true, $callback);
        $this->timerCallbacks->insert($timer, $timer->getTriggerTime());

        return $timer->getTimerId();
    }

    public function getConnectionInfo(int $clientId): ?ConnectionInfo
    {
        if (!$this->clients[$clientId]) {
            return null;
        }
        $name = stream_socket_get_name($this->sockets[$clientId], true);
        [$ip, $port] = explode(':', $name);
        $connectionInfo = new ConnectionInfo();
        $connectionInfo->setRemoteIp($ip);
        $connectionInfo->setRemotePort((int) $port);
        $connectionInfo->setServerFd((int) $this->resource);
        $connectionInfo->setServerPort($this->getServerConfig()->getPort()->getPort());
        $connectionInfo->setConnectTime($this->clients[$clientId]['connect_time'] ?? 0);
        $connectionInfo->setLastTime($this->clients[$clientId]['last_time'] ?? 0);

        return $connectionInfo;
    }
}
