<?php

declare(strict_types=1);

namespace kuiper\event\fixtures;

use kuiper\event\EventListenerInterface;

class FooEventListener implements EventListenerInterface
{
    public function __invoke($event): void
    {
    }

    public function getSubscribedEvent(): string
    {
        return FooEvent::class;
    }
}
