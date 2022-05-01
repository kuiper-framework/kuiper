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

namespace kuiper\resilience\core;

use Swoole\Atomic;

class SwooleAtomicCounter implements Counter
{
    private readonly Atomic $atomic;

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
