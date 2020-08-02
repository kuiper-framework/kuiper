<?php

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

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    public function __get($index)
    {
        return $this->get($index);
    }

    public function __set($index, $value)
    {
        $this->set($index, $value);
    }

    public function __isset($index)
    {
        return $this->has($index);
    }

    public function __unset($index)
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
