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

namespace kuiper\web\session;

use kuiper\web\exception\SessionNotStartException;

trait SessionTrait
{
    private bool $started = false;

    private bool $autoStart = false;

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    public function __get(string $index): mixed
    {
        return $this->get($index);
    }

    public function __set(string $index, mixed $value): void
    {
        $this->set($index, $value);
    }

    public function __isset(string $index): bool
    {
        return $this->has($index);
    }

    public function __unset(string $index): void
    {
        $this->remove($index);
    }

    public function isAutoStart(): bool
    {
        return $this->autoStart;
    }

    private function checkStart(): void
    {
        if ($this->isStarted()) {
            return;
        }
        if ($this->isAutoStart()) {
            $this->start();

            return;
        }
        throw new SessionNotStartException('Session is not start, call start function or set auto_start to true');
    }
}
