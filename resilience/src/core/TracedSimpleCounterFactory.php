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

class TracedSimpleCounterFactory extends AbstractCounterFactory
{
    /**
     * @var string[]
     */
    private array $actions = [];

    protected function createInternal(string $name): Counter
    {
        return new TracedSimpleCounter($this, $name);
    }

    public function trace(string $log): void
    {
        $this->actions[] = $log;
    }

    /**
     * @return string[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }
}
