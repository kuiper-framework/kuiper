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

use kuiper\swoole\ConnectionInfo;
use kuiper\swoole\constants\Event;
use kuiper\swoole\constants\ServerSetting;

class SocketWorker extends AbstractWorker
{
    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var resource
     */
    private $resource;
    /**
     * @var resource[]
     */
    private array $sockets = [];
    /**
     * @var array
     */
    private array $clients = [];

    /**
     * 只在 worker 进程中使用.
     *
     * @var array
     */
    private array $callbacks = [];

    /**
     * 只在 worker 进程中使用.
     *
     * @var int
     */
    private int $callbackId = 0;

    protected function work(): void
    {
        $read = $this->sockets;
        $write = $except = null;
        if (stream_select($read, $write, $except, 0)) {
            foreach ($read as $socket) {
                if ($socket === $this->resource) {
                    if ($clientSocketId = $this->accept()) {
                        $this->clients[$clientSocketId]['connect_time'] = time();
                        $this->dispatch(Event::CONNECT->value, [$clientSocketId, 0]);
                    }
                } else {
                    $data = $this->read($socket, $this->getSettings()->getInt(ServerSetting::SOCKET_BUFFER_SIZE));
                    if (!empty($data)) {
                        $this->clients[(int) $socket]['last_time'] = time();
                        $this->dispatch(Event::RECEIVE->value, [(int) $socket, 0, $data]);
                    } else {
                        $this->close((int) $socket);
                    }
                }
            }
        }
        $this->handleMessages();
    }

    public function sendTask(mixed $data, int $taskWorkerId, ?callable $onFinish): void
    {
        $task = new Task(
            taskWorkerId: $taskWorkerId,
            fromWorkerId: $this->getWorkerId(),
            callbackId: $this->callbackId++,
            data: $data
        );
        $this->callbacks[$task->getCallbackId()] = $onFinish;
        $this->getChannel()->push([MessageType::TASK, $task]);
    }

    protected function onStart(): void
    {
        $this->resource = $this->manager->getResource();
        $this->sockets[(int) $this->resource] = $this->resource;
    }

    protected function onStop(): void
    {
        foreach ($this->sockets as $socket) {
            if ($socket !== $this->resource) {
                $this->close((int) $socket);
            }
        }
    }

    private function accept(): bool|int
    {
        $socket = stream_socket_accept($this->resource, 0);
        //惊群
        if (false === $socket) {
            return false;
        }
        $socketId = (int) $socket;
        stream_set_blocking($socket, false);
        $this->sockets[$socketId] = $socket;
        if (count($this->sockets) - 1 > $this->getSettings()->getInt(ServerSetting::MAX_CONN)) {
            fclose($socket);

            return false;
        }

        // 设置写缓冲区
        stream_set_write_buffer($socket, $this->getSettings()->getInt(ServerSetting::BUFFER_OUTPUT_SIZE));

        return $socketId;
    }

    public function send(int $clientId, string $data): void
    {
        if (!isset($this->sockets[$clientId]) || $clientId === (int) $this->resource) {
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

    /**
     * @param resource $fp
     */
    private function read($fp, int $length): string
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

    public function close(int $socketId): void
    {
        if (isset($this->sockets[$socketId])) {
            fclose($this->sockets[$socketId]);
        }
        unset($this->sockets[$socketId], $this->clients[$socketId]);
        $this->dispatch(Event::CLOSE->value, [$socketId, 0]);
    }

    public function getConnectionInfo(int $clientId): ?ConnectionInfo
    {
        if (!isset($this->clients[$clientId])) {
            return null;
        }
        $name = stream_socket_get_name($this->sockets[$clientId], true);
        [$ip, $port] = explode(':', $name);
        return new ConnectionInfo(
            remoteIp: $ip,
            remotePort: (int) $port,
            serverPort: $this->getServerConfig()->getPort()->getPort(),
            serverFd: (int) $this->resource,
            connectTime: (int) ($this->clients[$clientId]['connect_time'] ?? 0),
            lastTime:(int) ($this->clients[$clientId]['last_time'] ?? 0)
        );
    }

    private function handleMessages(): void
    {
        $data = $this->getChannel()->pop(0);
        if (!empty($data) && 2 === count($data)) {
            /** @var Task $task */
            [$msgType, $task] = $data;
            switch ($msgType) {
                case MessageType::TICK:
                    $this->triggerTick();
                    break;
                case MessageType::TASK_RESULT:
                    if (isset($this->callbacks[$task->getCallbackId()])) {
                        call_user_func($this->callbacks[$task->getCallbackId()], $task->getResult());
                    }
                    break;
                case MessageType::TASK_FINISH:
                    unset($this->callbacks[$task->getCallbackId()]);
                    break;
            }
        }
    }
}
