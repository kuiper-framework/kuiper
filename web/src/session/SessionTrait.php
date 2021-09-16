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
    /**
     * @var bool
     */
    private $started = false;

    /**
     * @var bool
     */
    private $autoStart = false;

    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    /**
     * @return mixed|null
     */
    public function __get(string $index)
    {
        return $this->get($index);
    }

    /**
     * @param mixed $value
     */
    public function __set(string $index, $value): void
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
        if ($this->autoStart) {
            $this->start();

            return;
        }
        throw new SessionNotStartException('Session is not start, call start function or set auto_start to true');
    }
}
