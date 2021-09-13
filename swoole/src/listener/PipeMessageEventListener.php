<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\event\PipeMessageEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class PipeMessageEventListener implements EventListenerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * PipeMessageEventListener constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke($event): void
    {
        /** @var PipeMessageEvent $event */
        $this->eventDispatcher->dispatch($event->getMessage());
    }

    public function getSubscribedEvent(): string
    {
        return PipeMessageEvent::class;
    }
}
