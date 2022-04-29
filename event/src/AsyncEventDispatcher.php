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

namespace kuiper\event;

use kuiper\event\attribute\Async;
use kuiper\event\async\AsyncEventTask;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\task\QueueInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class AsyncEventDispatcher implements AsyncEventDispatcherInterface
{
    private ?ServerInterface $server = null;

    private ?QueueInterface $taskQueue = null;

    public function __construct(private  EventDispatcherInterface $delegateEventDispatcher)
    {
    }

    public function setServer(ServerInterface $server): void
    {
        $this->server = $server;
    }

    public function setTaskQueue(QueueInterface $taskQueue): void
    {
        $this->taskQueue = $taskQueue;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch(object $event)
    {
        if ($this->hasTaskWorker()) {
            $reflectionClass = new \ReflectionClass($event);
            if(count($reflectionClass->getAttributes(Async::class)) > 0 ) {
                $this->dispatchAsync($event);

                return $event;
            }
        }

        return $this->delegateEventDispatcher->dispatch($event);
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDelegateEventDispatcher(): EventDispatcherInterface
    {
        return $this->delegateEventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatchAsync(object $event): void
    {
        if ($this->hasTaskWorker()) {
            $this->taskQueue->put(new AsyncEventTask($event));
        } else {
            $this->delegateEventDispatcher->dispatch($event);
        }
    }

    /**
     * @return bool
     */
    private function hasTaskWorker(): bool
    {
        return isset($this->server, $this->taskQueue) && !$this->server->isTaskWorker();
    }
}
