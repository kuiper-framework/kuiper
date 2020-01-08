<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

interface DispatcherInterface
{
    /**
     * Dispatch task to processor.
     *
     * @param object $task
     */
    public function dispatch($task): void;
}
