<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\swoole\event\WorkerStartEvent;
use kuiper\swoole\SwooleServer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class WorkerStartEventListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param WorkerStartEvent $event
     */
    public function __invoke($event): void
    {
        $serverName = $event->getServer()->getServerConfig()->getServerName();
        $title = sprintf('%s: %s%s %d process', $serverName,
            ($event->getSwooleServer()->taskworker ? 'task ' : ''), SwooleServer::WORKER_PROCESS_NAME, $event->getWorkerId());
        @cli_set_process_title($title);
        $this->logger && $this->logger->debug("[WorkerStartEventListener] start worker {$title}");
    }

    public function getSubscribedEvent(): string
    {
        return WorkerStartEvent::class;
    }
}
