<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace kuiper\swoole\task;

use kuiper\swoole\event\TaskEvent;

interface TaskEventAwareInterface
{
    /**
     * @param TaskEvent $event
     */
    public function setTaskEvent(TaskEvent $event): void;
}