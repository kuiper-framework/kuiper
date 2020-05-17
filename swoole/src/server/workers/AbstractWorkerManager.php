<?php

declare(strict_types=1);

namespace kuiper\swoole\server\workers;

use kuiper\helper\Properties;
use kuiper\swoole\event\AbstractServerEvent;
use kuiper\swoole\exception\ServerStateException;
use kuiper\swoole\server\SelectTcpServer;
use kuiper\swoole\ServerConfig;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

abstract class AbstractWorkerManager implements LoggerAwareInterface, WorkerManagerInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var SelectTcpServer
     */
    protected $server;

    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var bool
     */
    protected $stopped = false;

    public function __construct(SelectTcpServer $server, LoggerInterface $logger)
    {
        $this->server = $server;
        $this->setLogger($logger);
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
        $this->logger->debug(static::TAG.'create listen socket', ['resource' => (int) $socket]);
    }

    protected function close(): void
    {
        if ($this->resource) {
            fclose($this->resource);
        }
        $this->resource = null;
    }

    private function getUri(): string
    {
        $serverPort = $this->server->getServerConfig()->getPort();

        return sprintf('tcp://%s:%d', $serverPort->getHost(), $serverPort->getPort());
    }

    protected function installSignal(): void
    {
        // stop
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
        // reload
        pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
        // ignore
        pcntl_signal(SIGPIPE, SIG_IGN);
        pcntl_signal(SIGCHLD, [$this, 'signalHandler']);
    }

    protected function dispatch(string $event, array $args): ?AbstractServerEvent
    {
        return $this->server->dispatch($event, $args);
    }

    protected function getServerConfig(): ServerConfig
    {
        return $this->server->getServerConfig();
    }

    protected function getSettings(): Properties
    {
        return $this->server->getSettings();
    }

    abstract public function signalHandler(int $signal): void;

    public function isStopped(): bool
    {
        return $this->stopped;
    }

    public function stop(): void
    {
        $this->stopped = true;
    }

    public function getMasterPid(): int
    {
        return $this->server->getMasterPid();
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    protected function getResourceId(): int
    {
        return (int) $this->resource;
    }
}
