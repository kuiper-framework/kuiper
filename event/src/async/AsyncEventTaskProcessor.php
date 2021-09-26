<?php

declare(strict_types=1);

namespace kuiper\event\async;

use kuiper\swoole\task\ProcessorInterface;
use kuiper\swoole\task\TaskInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class AsyncEventTaskProcessor implements ProcessorInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * AsyncEventTaskProcessor constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function process(TaskInterface $task): void
    {
        /** @var AsyncEventTask $task */
        $this->eventDispatcher->dispatch($task->getEvent());
    }
}
