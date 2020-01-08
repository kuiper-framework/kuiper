<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

interface QueueInterface
{
    /**
     * Puts task to job queue.
     *
     * @param object $task
     */
    public function put($task, int $workerId = -1, callable $onFinish = null): int;
}
