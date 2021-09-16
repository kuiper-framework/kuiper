<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\swoole\fixtures;

use kuiper\swoole\task\ProcessorInterface;
use kuiper\swoole\task\TaskInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class FooTaskProcessor implements ProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function process(TaskInterface $task)
    {
        $this->logger->info(static::TAG.'handle task', ['id' => $task->getTaskId(), 'task' => $task]);
        /** @var FooTask $task */
        $task->incr();

        return $task->getTimes();
    }
}
