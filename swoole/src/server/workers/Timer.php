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
    /**
     * @var int
     */
    private $timerId;

    /**
     * @var int
     */
    private $interval;

    /**
     * @var int
     */
    private $triggerTime;

    /**
     * @var callable
     */
    private $callback;

    /**
     * @var bool
     */
    private $once;

    /**
     * Timer constructor.
     */
    public function __construct(int $timerId, int $interval, bool $once, callable $callback)
    {
        $this->timerId = $timerId;
        $this->interval = $interval;
        $this->once = $once;
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
