<?php

namespace kuiper\swoole\task;

use kuiper\swoole\event\TaskEvent;

interface TaskEventAwareInterface
{
    /**
     * @param TaskEvent $event
     */
    public function setTaskEvent(TaskEvent $event): void;
}