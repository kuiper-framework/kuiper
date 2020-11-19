<?php

declare(strict_types=1);

namespace kuiper\event;

interface EventSubscriberInterface
{
    /**
     * @param object $event
     */
    public function __invoke($event): void;

    /**
     * Returns the event name this event listener wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents(): array;
}
