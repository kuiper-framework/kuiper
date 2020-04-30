<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

class TaskEvent extends AbstractServerEvent
{
    /**
     * @var int
     */
    private $taskId;

    /**
     * @var int
     */
    private $fromWorkerId;

    /**
     * @var mixed
     */
    private $data;

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function setTaskId(int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function getFromWorkerId(): int
    {
        return $this->fromWorkerId;
    }

    public function setFromWorkerId(int $fromWorkerId): void
    {
        $this->fromWorkerId = $fromWorkerId;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }
}
