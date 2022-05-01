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

class SimpleCounter implements Counter
{
    /**
     * @var int
     */
    private int $count = 0;

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
