<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

use kuiper\swoole\event\TaskEvent;
use kuiper\swoole\server\ServerInterface;

interface TaskInterface
{
    /**
     * @param TaskEvent $event
     */
    public function setTaskEvent(TaskEvent $event): void;

    /**
     * @return ServerInterface
     */
    public function getServer(): ServerInterface;

    /**
     * @return int
     */
    public function getTaskId(): int;

    /**
     * @return int
     */
    public function getFromWorkerId(): int;
}
