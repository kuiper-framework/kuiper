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

namespace kuiper\swoole\task;

use kuiper\swoole\event\TaskEvent;
use kuiper\swoole\server\ServerInterface;

abstract class AbstractTask implements TaskInterface
{
    /**
     * @var TaskEvent|null
     */
    private $event;

    public function setTaskEvent(TaskEvent $event): void
    {
        $this->event = $event;
    }

    public function getServer(): ServerInterface
    {
        return $this->event->getServer();
    }

    public function getTaskId(): int
    {
        return $this->event->getTaskId();
    }

    public function getFromWorkerId(): int
    {
        return $this->event->getFromWorkerId();
    }
}
