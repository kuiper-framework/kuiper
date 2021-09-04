<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

interface ProcessorInterface
{
    /**
     * Processes task.
     */
    public function process(TaskInterface $task);
}
