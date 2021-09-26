<?php

declare(strict_types=1);

namespace kuiper\event;

use Psr\EventDispatcher\EventDispatcherInterface;

interface AsyncEventDispatcherInterface extends EventDispatcherInterface
{
    /**
     * @param object $event
     */
    public function dispatchAsync(object $event): void;
}
