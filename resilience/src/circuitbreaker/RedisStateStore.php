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

namespace kuiper\resilience\circuitbreaker;

use Redis;

class RedisStateStore implements StateStore
{
    public function __construct(private readonly Redis $redis, private readonly string $keyPrefix = 'circuitbreaker')
    {
    }

    public function getState(string $name): State
    {
        $value = (int) $this->redis->get($this->keyPrefix.$name);

        return State::tryFrom($value) ?? State::CLOSED;
    }

    public function setState(string $name, State $state): void
    {
        $value = [
            $this->keyPrefix.$name => $state->value,
        ];
        if (State::OPEN === $state) {
            $value[$this->keyPrefix.'open'.$name] = (int) (microtime(true) * 1000);
        }
        $this->redis->mSet($value);
    }

    public function getOpenAt(string $name): int
    {
        $values = $this->redis->mGet([
            $this->keyPrefix.$name,
            $this->keyPrefix.'open'.$name,
        ]);
        if (State::OPEN->value === ((int) $values[0])) {
            return (int) $values[1];
        }

        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(string $name): void
    {
        $this->redis->del($this->keyPrefix.$name);
    }
}
