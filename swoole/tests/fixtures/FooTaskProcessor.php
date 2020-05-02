<?php

declare(strict_types=1);

namespace kuiper\swoole\fixtures;

use kuiper\swoole\task\ProcessorInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class FooTaskProcessor implements ProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const TAG = '['.__CLASS__.'] ';

    /**
     * @param FooTask $task
     */
    public function process($task)
    {
        $this->logger->info(self::TAG.'handle task', ['task' => $task]);
        $task->incr();
    }
}
