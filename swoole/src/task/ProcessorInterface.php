<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

interface ProcessorInterface
{
    /**
     * Processes task.
     *
     * @param object $task
     *
     * @return mixed
     */
    public function process($task);
}
