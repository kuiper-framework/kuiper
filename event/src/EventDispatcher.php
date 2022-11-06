<?php

namespace kuiper\event;

use Psr\EventDispatcher\EventDispatcherInterface;

class EventDispatcher implements EventDispatcherInterface, EventRegistryInterface
{
    private \Symfony\Component\EventDispatcher\EventDispatcher $delegateEventDispatcher;

    public function __construct()
    {
        $this->delegateEventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
    }

    public function dispatch(object $event)
    {
        return $this->delegateEventDispatcher->dispatch($event);
    }

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->delegateEventDispatcher->addListener($eventName, $listener, $priority);
    }

    public function reset(): void
    {
        $this->delegateEventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
    }
}