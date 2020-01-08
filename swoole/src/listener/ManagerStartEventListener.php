<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\swoole\event\ManagerStartEvent;
use kuiper\swoole\SwooleServer;

class ManagerStartEventListener implements EventListenerInterface
{
    /**
     * @param ManagerStartEvent $event
     */
    public function __invoke($event): void
    {
        @cli_set_process_title(sprintf('%s: %s process', $event->getServer()->getServerConfig()->getServerName(), SwooleServer::MANAGER_PROCESS_NAME));
    }

    public function getSubscribedEvent(): string
    {
        return ManagerStartEvent::class;
    }
}
