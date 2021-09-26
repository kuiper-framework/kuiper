<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

use Swoole\Atomic;

class SwooleAtomicCounter implements Counter
{
    /**
     * @var Atomic
     */
    private $atomic;

    /**
     * SwooleAtomicCounter constructor.
     */
    public function __construct()
    {
        $this->atomic = new Atomic();
    }

    public function get(): int
    {
        return $this->atomic->get();
    }

    public function set(int $value): void
    {
        $this->atomic->set($value);
    }

    public function increment(int $value = 1): int
    {
        return $this->atomic->add($value);
    }

    public function decrement(int $value = 1): int
    {
        return $this->atomic->sub($value);
    }
}
