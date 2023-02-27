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

namespace kuiper\swoole\server\workers;

use InvalidArgumentException;
use kuiper\swoole\constants\Event;

class TaskWorker extends AbstractWorker
{
    protected const TAG = '['.__CLASS__.'] ';

    private ?Task $task = null;

    protected function work(): void
    {
        $data = $this->getChannel()->pop();
        if (!empty($data) && 2 === count($data)) {
            /** @var Task $task */
            [$msgType, $task] = $data;
            if (MessageType::TICK === $msgType) {
                $this->triggerTick();

                return;
            }
            if (MessageType::TASK !== $msgType) {
                throw new InvalidArgumentException("TaskWorker only accept task message: type=$msgType");
            }
            $this->task = $task;
            $this->dispatch(Event::TASK->value, [$task->getTaskId(), $task->getFromWorkerId(), $task->getData()]);
            unset($this->task);
            $this->getChannel()->push([MessageType::TASK_FINISH, $task]);
        }
    }

    /**
     * @param mixed $data
     */
    public function finish($data): void
    {
        $ret = clone $this->task;
        $ret->setResult($data);
        $this->getChannel()->push([MessageType::TASK_RESULT, $ret]);
    }

    /**
     * @return Task
     */
    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(Task $task): void
    {
        $this->task = $task;
        $this->getChannel()->push([MessageType::TASK, $task]);
    }

    public function done(): void
    {
        $this->task = null;
    }

    public function isIdle(): bool
    {
        return isset($this->task);
    }
}
