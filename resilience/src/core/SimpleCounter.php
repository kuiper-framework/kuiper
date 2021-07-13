<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class SimpleCounter implements Counter
{
    /**
     * @var int
     */
    private $count = 0;

    public function increment(): void
    {
        ++$this->count;
    }

    public function get(): int
    {
        return $this->count;
    }

    public function incrementAndGet(): int
    {
        return ++$this->count;
    }
}
