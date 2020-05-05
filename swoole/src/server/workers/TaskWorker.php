<?php

declare(strict_types=1);

namespace kuiper\swoole\server\workers;

use kuiper\swoole\constants\Event;

class TaskWorker extends AbstractWorker
{
    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var Task
     */
    private $task;

    protected function work(): void
    {
        /** @var Task $task */
        $data = $this->getChannel()->receive();
        if ($data && 2 === count($data)) {
            [$msgType, $task] = $data;
            if (MessageType::TICK === $msgType) {
                $this->triggerTick();

                return;
            }
            if (MessageType::TASK !== $msgType) {
                throw new \InvalidArgumentException("TaskWorker only accept task message: type=$msgType");
            }
            $this->task = $task;
            $this->dispatch(Event::TASK, [$task->getTaskId(), $task->getFromWorkerId(), $task->getData()]);
            unset($this->task);
            $task->setData(null);
            $this->getChannel()->send([MessageType::TASK_FINISH, $task]);
        }
    }

    public function finish($data): void
    {
        $ret = clone $this->task;
        $ret->setData(null);
        $ret->setResult($data);
        $this->getChannel()->send([MessageType::TASK_RESULT, $ret]);
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
        $this->getChannel()->send([MessageType::TASK, $task]);
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
