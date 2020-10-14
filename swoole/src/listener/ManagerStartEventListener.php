<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\constants\ProcessType;
use kuiper\swoole\event\ManagerStartEvent;
use Webmozart\Assert\Assert;

class ManagerStartEventListener implements EventListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke($event): void
    {
        Assert::isInstanceOf($event, ManagerStartEvent::class);
        /* @var ManagerStartEvent $event */
        @cli_set_process_title(sprintf('%s: %s process',
            $event->getServer()->getServerConfig()->getServerName(), ProcessType::MANAGER));
    }

    public function getSubscribedEvent(): string
    {
        return ManagerStartEvent::class;
    }
}
