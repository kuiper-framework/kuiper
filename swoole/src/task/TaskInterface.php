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

interface TaskInterface
{
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
