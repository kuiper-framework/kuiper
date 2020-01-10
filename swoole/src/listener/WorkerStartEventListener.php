<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\swoole\event\WorkerStartEvent;
use kuiper\swoole\SwooleServer;

class WorkerStartEventListener implements EventListenerInterface
{
    /**
     * @param WorkerStartEvent $event
     */
    public function __invoke($event): void
    {
        $serverName = $event->getServer()->getServerConfig()->getServerName();
        @cli_set_process_title(sprintf('%s: %s%s %d process', $serverName,
            ($event->getSwooleServer()->taskworker ? 'task ' : ''), SwooleServer::WORKER_PROCESS_NAME, $event->getWorkerId()));
    }

    public function getSubscribedEvent(): string
    {
        return WorkerStartEvent::class;
    }
}
