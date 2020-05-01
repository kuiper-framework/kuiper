<?php

declare(strict_types=1);

namespace kuiper\swoole\server;

use kuiper\swoole\constants\Event;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\exception\ServerStateException;

class SelectTcpServer extends AbstractServer
{
    /**
     * @var resource
     */
    private $resource;

    /**
     * @var array
     */
    private $sockets;

    /**
     * @var int
     */
    private $masterPid;

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        $this->getSettings()->mergeIfNotExists([
            ServerSetting::BUFFER_OUTPUT_SIZE => 2097152,
            ServerSetting::SOCKET_BUFFER_SIZE => 8192,
            ServerSetting::MAX_CONN => 1000,
        ]);
        $this->masterPid = getmypid();
        $this->dispatch(Event::BOOTSTRAP, []);
        // TODO 信号控制
        $uri = $this->getUri();
        $socket = stream_socket_server($uri, $errno, $err);

        if (!$socket) {
            throw new ServerStateException("Cannot listen to $uri, code=$errno, message=$err");
        }
        stream_set_blocking($socket, false);
        $this->resource = $socket;
        $this->sockets[(int) $socket] = $socket;
        $this->spawn();
        $this->dispatch(Event::WORKER_START, [getmypid()]);
        $this->loop();
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function reload(): void
    {
    }

    public function task($data, $workerId = -1, $onFinish = null)
    {
    }

    public function finish($data): void
    {
    }

    public function getMasterPid(): int
    {
        return $this->masterPid;
    }

    public function isTaskWorker(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function send(int $clientId, string $data): void
    {
        // TODO 写异常使用 ERROR 事件
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

    private function getUri(): string
    {
        $serverPort = $this->getServerConfig()->getPort();

        return sprintf('tcp://%s:%d', $serverPort->getHost(), $serverPort->getPort());
    }

    private function spawn(): void
    {
        $num = $this->getSettings()->getInt(ServerSetting::WORKER_NUM);
        if ($num < 2) {
            return;
        }
        if (!extension_loaded('pcntl')) {
            die(__METHOD__.' require pcntl extension');
        }
        for ($i = 0; $i < $num; ++$i) {
            $pid = pcntl_fork();
            if (0 === $pid) {
                break;
            }
        }
    }

    private function loop(): void
    {
        while (true) {
            $read = $this->sockets;
            $write = $except = null;
            if (stream_select($read, $write, $except, null)) {
                foreach ($read as $socket) {
                    if ($socket === $this->resource) {
                        if ($clientSocketId = $this->accept()) {
                            $this->dispatch(Event::CONNECT, [$clientSocketId, 0]);
                        }
                    } else {
                        $data = $this->read($socket, $this->getSettings()->getInt(ServerSetting::SOCKET_BUFFER_SIZE));
                        if (!empty($data)) {
                            $this->dispatch(Event::RECEIVE, [(int) $socket, 0, $data]);
                        } else {
                            $this->close($socket);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return false|int
     */
    private function accept()
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

    /**
     * 获取连接信息.
     */
    public function getConnectionInfo(int $fd): array
    {
        $name = stream_socket_get_name($this->sockets[$fd], true);
        [$ip, $port] = explode(':', $name);

        return ['remote_port' => $port, 'remote_ip' => $ip];
    }

    protected function close($socket): void
    {
        $socketId = (int) $socket;
        if (isset($this->sockets[$socketId])) {
            fclose($this->sockets[$socketId]);
        }
        $this->sockets[$socketId] = null;
        unset($this->sockets[$socketId]);
        $this->dispatch(Event::CLOSE, [$socketId, 0]);
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
}
