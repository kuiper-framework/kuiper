<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\constants\ProcessType;
use kuiper\swoole\event\ManagerStartEvent;

class ManagerStartEventListener implements EventListenerInterface
{
    /**
     * @param ManagerStartEvent $event
     */
    public function __invoke($event): void
    {
        @cli_set_process_title(sprintf('%s: %s process', $event->getServer()->getServerConfig()->getServerName(), ProcessType::MANAGER));
    }

    public function getSubscribedEvent(): string
    {
        return ManagerStartEvent::class;
    }
}
