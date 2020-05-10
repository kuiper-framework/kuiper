<?php

declare(strict_types=1);

namespace kuiper\swoole\livereload;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\event\WorkerStartEvent;
use kuiper\swoole\server\ServerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Reloader implements LoggerAwareInterface, EventListenerInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var FileWatcherInterface
     */
    private $fileWatcher;

    /**
     * check interval in seconds.
     *
     * @var int
     */
    private $interval;

    public function __construct(FileWatcherInterface $fileWatcher, int $interval, ?LoggerInterface $logger)
    {
        $this->fileWatcher = $fileWatcher;
        $this->interval = $interval;
        $this->setLogger($logger ?? new NullLogger());
    }

    public function onWorkerStart(WorkerStartEvent $event): void
    {
        // This method will be called for each started worker.
        // We will register our tick function on the first worker.
        $server = $event->getServer();
        if (0 === $event->getWorkerId()) {
            $server->tick($this->interval * 1000, function () use ($server) {
                $this->onTick($server);
            });
        }
    }

    public function onTick(ServerInterface $server): void
    {
        $changedFilePaths = $this->fileWatcher->getChangedPaths();
        if ($changedFilePaths) {
            $this->logger->debug(static::TAG.'Reloading due to file changes', ['files' => $changedFilePaths]);
            $server->reload();
        }
    }

    /**
     * @param WorkerStartEvent $event
     */
    public function __invoke($event): void
    {
        $this->onWorkerStart($event);
    }

    public function getSubscribedEvent(): string
    {
        return WorkerStartEvent::class;
    }
}
