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

interface QueueInterface
{
    /**
     * Puts task to job queue.
     */
    public function put(TaskInterface $task, int $workerId = -1, callable $onFinish = null): int;
}
