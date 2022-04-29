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

namespace kuiper\event\async;

use kuiper\swoole\task\AbstractTask;

class AsyncEventTask extends AbstractTask
{
    public function __construct(private readonly object $event)
    {
    }

    /**
     * @return object
     */
    public function getEvent(): object
    {
        return $this->event;
    }
}
