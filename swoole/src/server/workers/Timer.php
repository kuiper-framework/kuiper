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

namespace kuiper\swoole\server\workers;

class Timer
{
    private int $triggerTime;

    /**
     * @var callable
     */
    private $callback;

    /**
     * Timer constructor.
     */
    public function __construct(
        private readonly int $timerId,
        private readonly int $interval,
        private readonly bool $once,
        callable $callback)
    {
        $this->triggerTime = time() + $interval;
        $this->callback = $callback;
    }

    public function isOnce(): bool
    {
        return $this->once;
    }

    public function trigger(): void
    {
        try {
            call_user_func($this->callback);
        } finally {
            $this->triggerTime = time() + $this->interval;
        }
    }

    public function getTimerId(): int
    {
        return $this->timerId;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function getTriggerTime(): int
    {
        return $this->triggerTime;
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }
}
