<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

interface EventListenerInterface
{
    /**
     * @param object $event
     */
    public function __invoke($event): void;

    public function getSubscribedEvent(): string;
}
