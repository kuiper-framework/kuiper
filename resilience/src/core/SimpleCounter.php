<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class SimpleCounter implements Counter
{
    /**
     * @var int
     */
    private $count = 0;

    public function increment(int $value = 1): int
    {
        $this->count += $value;

        return $this->count;
    }

    public function get(): int
    {
        return $this->count;
    }

    public function set(int $value): void
    {
        $this->count = $value;
    }

    public function decrement(int $value = 1): int
    {
        $this->count -= $value;

        return $this->count;
    }
}
