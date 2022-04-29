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

namespace kuiper\event\async;

use kuiper\swoole\task\ProcessorInterface;
use kuiper\swoole\task\TaskInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class AsyncEventTaskProcessor implements ProcessorInterface
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function process(TaskInterface $task): void
    {
        /** @var AsyncEventTask $task */
        $this->eventDispatcher->dispatch($task->getEvent());
    }
}
