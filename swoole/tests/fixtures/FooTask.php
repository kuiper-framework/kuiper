<?php

declare(strict_types=1);

namespace kuiper\swoole\fixtures;

use kuiper\swoole\task\AbstractTask;

class FooTask extends AbstractTask implements \JsonSerializable
{
    private $arg;

    private $times;

    /**
     * FooTask constructor.
     *
     * @param $arg
     */
    public function __construct($arg)
    {
        $this->arg = $arg;
        $this->times = 1;
    }

    /**
     * @return mixed
     */
    public function getArg()
    {
        return $this->arg;
    }

    public function getTimes(): int
    {
        return $this->times;
    }

    public function incr()
    {
        ++$this->times;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
