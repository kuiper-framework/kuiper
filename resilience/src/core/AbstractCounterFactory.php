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

abstract class AbstractCounterFactory implements CounterFactory
{
    /**
     * @var Counter[]
     */
    private array $counters = [];

    /**
     * {@inheritDoc}
     */
    public function create(string $name): Counter
    {
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = $this->createInternal($name);
        }

        return $this->counters[$name];
    }

    abstract protected function createInternal(string $name): Counter;
}
