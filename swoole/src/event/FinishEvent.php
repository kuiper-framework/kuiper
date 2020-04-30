<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

class FinishEvent extends AbstractServerEvent
{
    /**
     * @var int
     */
    private $taskId;

    /**
     * @var string
     */
    private $result;

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function setTaskId(int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function setResult(string $result): void
    {
        $this->result = $result;
    }

    public function getResult(): string
    {
        return $this->result;
    }
}
