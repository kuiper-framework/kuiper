<?php

declare(strict_types=1);

namespace kuiper\swoole\fixtures;

use kuiper\swoole\task\ProcessorInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class FooTaskProcessor implements ProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param FooTask $task
     */
    public function process($task)
    {
        $this->logger->info('handle task', ['task' => $task]);
        $task->incr();
    }
}
