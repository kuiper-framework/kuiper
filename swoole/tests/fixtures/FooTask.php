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
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
