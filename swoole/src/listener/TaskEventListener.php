<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\event\TaskEvent;
use kuiper\swoole\task\DispatcherInterface;

class TaskEventListener implements EventListenerInterface
{
    /**
     * @var DispatcherInterface
     */
    private $taskDispatcher;

    /**
     * TaskEventListener constructor.
     */
    public function __construct(DispatcherInterface $taskProcessor)
    {
        $this->taskDispatcher = $taskProcessor;
    }

    /**
     * @param TaskEvent $event
     */
    public function __invoke($event): void
    {
        $this->taskDispatcher->dispatch($event->getData());
    }

    public function getSubscribedEvent(): string
    {
        return TaskEvent::class;
    }
}
