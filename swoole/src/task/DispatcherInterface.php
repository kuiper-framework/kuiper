<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

use kuiper\swoole\event\TaskEvent;

interface DispatcherInterface
{
    /**
     * Dispatch task to processor.
     */
    public function dispatch(TaskEvent $task): void;
}
