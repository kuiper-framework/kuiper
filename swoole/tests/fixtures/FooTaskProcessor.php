<?php

declare(strict_types=1);

namespace kuiper\swoole\fixtures;

use kuiper\swoole\task\ProcessorInterface;
use kuiper\swoole\task\Task;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class FooTaskProcessor implements ProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function process(Task $task)
    {
        $this->logger->info(static::TAG.'handle task', ['task' => $task->getPayload()]);
        $task->getPayload()->incr();
    }
}
