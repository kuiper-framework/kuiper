<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

interface QueueInterface
{
    /**
     * Puts task to job queue.
     */
    public function put(TaskInterface $task, int $workerId = -1, callable $onFinish = null): int;
}
