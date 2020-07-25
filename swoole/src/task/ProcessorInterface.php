<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

interface ProcessorInterface
{
    /**
     * Processes task.
     *
     * @return mixed
     */
    public function process(Task $task);
}
