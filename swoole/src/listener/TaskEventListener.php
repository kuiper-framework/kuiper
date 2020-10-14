<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\event\TaskEvent;
use kuiper\swoole\task\DispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Webmozart\Assert\Assert;

class TaskEventListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    protected const TAG = '['.__CLASS__.'] ';
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
     * {@inheritdoc}
     */
    public function __invoke($event): void
    {
        Assert::isInstanceOf($event, TaskEvent::class);
        /* @var TaskEvent $event */
        $this->taskDispatcher->dispatch($event);
    }

    public function getSubscribedEvent(): string
    {
        return TaskEvent::class;
    }
}
