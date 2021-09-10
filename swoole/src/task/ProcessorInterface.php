<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

interface ProcessorInterface
{
    /**
     * Processes task.
     *
     * @return mixed|void
     */
    public function process(TaskInterface $task);
}
