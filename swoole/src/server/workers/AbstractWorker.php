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

    protected const TAG = '['.__CLASS__.'] ';

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
     * @var \SplPriorityQueue
     */
    private $tickCallbacks;

    /**
     * @var int
     */
    private $timerId = 0;

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
        $this->tickCallbacks = new \SplPriorityQueue();
        $this->channel->child();
        $this->installSignal();

        $this->onStart();
        $this->dispatch(Event::WORKER_START, [$this->workerId]);
        while (!$this->stopped) {
            $this->setErrorHandler();
            $this->work();
            $this->restoreErrorHandler();
        }
        $this->channel->close();
        $this->onStop();
        $this->dispatch(Event::WORKER_STOP, [$this->workerId]);
    }

    public function tick(int $millisecond, callable $callback): int
    {
        $second = (int) ($millisecond / 1000);
        if ($second <= 0) {
            $second = 1;
        }
        $timer = new Timer($this->timerId++, $second, $callback);
        $this->tickCallbacks->insert($timer, $timer->getTriggerTime());

        return $timer->getTimerId();
    }

    protected function triggerTick(): void
    {
        $time = time();
        while (!$this->tickCallbacks->isEmpty()) {
            /** @var Timer $top */
            $top = $this->tickCallbacks->top();
            if ($top->getTriggerTime() > $time) {
                break;
            }
            /** @var Timer $timer */
            $timer = $this->tickCallbacks->extract();
            $timer->trigger();
            $this->tickCallbacks->insert($timer, $timer->getTriggerTime());
        }
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
}
