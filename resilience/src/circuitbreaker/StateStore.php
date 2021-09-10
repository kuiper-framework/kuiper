<?php

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
}
