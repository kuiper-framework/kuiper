<?php

namespace kuiper\event;

interface EventRegistryInterface
{
    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param int $priority The higher this value, the earlier an event
     *                      listener will be triggered in the chain (defaults to 0)
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;

    /**
     * Removes all event listeners
     */
    public function reset(): void;
}