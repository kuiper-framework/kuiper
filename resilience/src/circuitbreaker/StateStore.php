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

interface StateStore
{
    /**
     * @param string $name
     *
     * @return State
     */
    public function getState(string $name): State;

    /**
     * @param string $name
     * @param State  $state
     */
    public function setState(string $name, State $state): void;

    /**
     * @return int
     */
    public function getOpenAt(string $name): int;

    /**
     * @param string $name
     */
    public function clear(string $name): void;
}
