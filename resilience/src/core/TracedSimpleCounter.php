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

class TracedSimpleCounter extends SimpleCounter
{
    /**
     * TracedSimpleCounter constructor.
     */
    public function __construct(private readonly TracedSimpleCounterFactory $factory, private readonly string $name)
    {
    }

    public function increment(int $value = 1): int
    {
        $this->factory->trace(implode("\t", [$this->name, __METHOD__, $value]));

        return parent::increment($value);
    }

    public function get(): int
    {
        $this->factory->trace(implode("\t", [$this->name, __METHOD__]));

        return parent::get();
    }

    public function set(int $value): void
    {
        $this->factory->trace(implode("\t", [$this->name, __METHOD__, $value]));
        parent::set($value);
    }

    public function decrement(int $value = 1): int
    {
        $this->factory->trace(implode("\t", [$this->name, __METHOD__, $value]));

        return parent::decrement($value);
    }
}
