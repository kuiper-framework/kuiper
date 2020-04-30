<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

class WorkerExitEvent extends AbstractServerEvent
{
    /**
     * @var int
     */
    private $workerId;

    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    public function setWorkerId(int $workerId): void
    {
        $this->workerId = $workerId;
    }
}
