<?php

declare(strict_types=1);

declare(ticks=1);

namespace kuiper\swoole\server\workers;

use kuiper\helper\Properties;
use kuiper\swoole\constants\Event;
use kuiper\swoole\event\AbstractServerEvent;
use kuiper\swoole\server\SelectTcpServer;
use kuiper\swoole\ServerConfig;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

abstract class AbstractWorker implements WorkerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var SelectTcpServer
     */
    protected $master;

    /**
     * @var int
     */
    private $workerId;

    /**
     * @var SocketChannel
     */
    private $channel;

    /**
     * @var int
     */
    private $pid;

    /**
     * @var bool
     */
    private $stopped;

    /**
     * AbstractWorker constructor.
     */
    public function __construct(SelectTcpServer $master, SocketChannel $channel, int $pid, int $workerId, LoggerInterface $logger)
    {
        $this->master = $master;
        $this->workerId = $workerId;
        $this->channel = $channel;
        $this->setLogger($logger);
        $this->pid = $pid > 0 ? $pid : getmypid();
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    public function getChannel(): SocketChannel
    {
        return $this->channel;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function start(): void
    {
        $this->channel->child();
        $this->installSignal();

        $this->onStart();
        $this->dispatch(Event::WORKER_START, [$this->workerId]);
        while (!$this->stopped) {
            $this->work();
        }
        $this->channel->close();
        $this->onStop();
        $this->dispatch(Event::WORKER_EXIT, [$this->workerId]);
    }

    protected function dispatch(string $event, array $args): ?AbstractServerEvent
    {
        return $this->master->dispatch($event, $args);
    }

    private function installSignal(): void
    {
        // uninstall stop signal handler
        pcntl_signal(SIGINT, SIG_IGN);
        // uninstall reload signal handler
        pcntl_signal(SIGUSR1, SIG_IGN);

        pcntl_signal(SIGINT, [$this, 'signalHandler']);
        pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
    }

    public function signalHandler($signal): void
    {
        switch ($signal) {
            // Stop.
            case SIGINT:
            case SIGUSR1:
                $this->stop();
                break;
        }
    }

    protected function getSettings(): Properties
    {
        return $this->master->getSettings();
    }

    protected function getServerConfig(): ServerConfig
    {
        return $this->master->getServerConfig();
    }

    abstract protected function work(): void;

    protected function onStart(): void
    {
    }

    protected function onStop(): void
    {
    }

    protected function stop(): void
    {
        $this->stopped = true;
    }
}
