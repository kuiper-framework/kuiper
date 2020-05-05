<?php

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
     * Timer constructor.
     */
    public function __construct(int $timerId, int $interval, callable $callback)
    {
        $this->timerId = $timerId;
        $this->interval = $interval;
        $this->triggerTime = time() + $interval;
        $this->callback = $callback;
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
