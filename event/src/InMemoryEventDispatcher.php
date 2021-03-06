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

class InMemoryEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var object[]
     */
    private $events = [];

    /**
     * {@inheritdoc}
     */
    public function dispatch(object $event)
    {
        return $this->events[] = $event;
    }

    /**
     * @return object[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function clear(): void
    {
        $this->events = [];
    }
}
