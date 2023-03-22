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

namespace kuiper\event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;

class EventDispatcher implements EventDispatcherInterface, EventRegistryInterface
{
    private SymfonyEventDispatcher $delegateEventDispatcher;

    public function __construct()
    {
        $this->delegateEventDispatcher = new SymfonyEventDispatcher();
    }

    public function dispatch(object $event)
    {
        return $this->delegateEventDispatcher->dispatch($event);
    }

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->delegateEventDispatcher->addListener($eventName, $listener, $priority);
    }

    public function reset(): void
    {
        $this->delegateEventDispatcher = new SymfonyEventDispatcher();
    }

    public function getDelegateEventDispatcher(): SymfonyEventDispatcher
    {
        return $this->delegateEventDispatcher;
    }
}
