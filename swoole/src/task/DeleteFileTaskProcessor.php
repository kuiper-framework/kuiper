<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

use Swoole\Timer;

class DeleteFileTaskProcessor implements ProcessorInterface
{
    /**
     * @param DeleteFileTask $task
     */
    public function process($task)
    {
        if ($task->getDelay() > 0) {
            Timer::after($task->getDelay(), static function () use ($task) {
                @unlink($task->getFileName());
            });
        } else {
            @unlink($task->getFileName());
        }
    }
}
