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

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\event\WorkerExitEvent;
use Swoole\Timer;

class WorkerExitEventListener implements EventListenerInterface
{
    public function __invoke($event): void
    {
        Timer::clearAll();
    }

    public function getSubscribedEvent(): string
    {
        return WorkerExitEvent::class;
    }
}
