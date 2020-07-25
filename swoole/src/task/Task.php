<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

use kuiper\swoole\server\ServerInterface;

class Task
{
    /**
     * @var ServerInterface
     */
    private $server;

    /**
     * @var int
     */
    private $workerId;

    /**
     * @var int
     */
    private $taskId;

    /**
     * @var object
     */
    private $payload;

    /**
     * Task constructor.
     */
    public function __construct(ServerInterface $server, int $workerId, int $taskId, object $payload)
    {
        $this->server = $server;
        $this->workerId = $workerId;
        $this->taskId = $taskId;
        $this->payload = $payload;
    }

    public function getServer(): ServerInterface
    {
        return $this->server;
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function getPayload(): object
    {
        return $this->payload;
    }
}
